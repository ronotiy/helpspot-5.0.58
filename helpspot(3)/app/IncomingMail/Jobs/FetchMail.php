<?php

namespace HS\IncomingMail\Jobs;

use Exception;
use HS\Mailbox;
use HS\Cloud\IsHosted;
use HS\Jobs\AdministratesJobs;
use HS\IncomingMail\Mailboxes\Imap;

use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class FetchMail extends MailJobBase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, AdministratesJobs, IsHosted;

    protected string $jobName = 'Fetching New Emails';
    protected string $jobCategory = 'mail.incoming';

    /**
     * @var Mailbox
     */
    private Mailbox $mailbox;

    /**
     * @var string
     */
    private string $transaction;

    /**
     * The maximum number of exceptions to allow before failing.
     *
     * @var int
     */
    public int $maxExceptions = 1;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     *
     * @param Mailbox $mailbox
     * @param string $transaction
     */
    public function __construct(Mailbox $mailbox, $transaction = '')
    {
        $this->mailbox = $mailbox;
        $this->transaction = $transaction;
    }

    /**
     * Execute the job.
     *
     * @return bool
     * @throws Exception
     */
    public function handle()
    {
        $this->loadTheWorld();

        if ($this->mailbox->mailboxIsBusy()) {
            Log::debug('['.get_class($this).'] mailbox is being checked concurrently, bailing out', [
                'xMailbox' => $this->mailbox->xMailbox,
                'sUsername' => $this->mailbox->sUsername,
                'sHostname' => $this->mailbox->sHostname,
                'transaction' => $this->transaction,
            ]);
            return true;
        }

        try {
            $box = new Imap($this->mailbox);
            $box->connect();
            Log::debug('['.get_class($this).'] connected to mailbox', [
                'xMailbox' => $this->mailbox->xMailbox,
                'sUsername' => $this->mailbox->sUsername,
                'sHostname' => $this->mailbox->sHostname,
                'transaction' => $this->transaction,
            ]);
        } catch(\Exception $e) {
            $this->mailbox->setMailboxAvailable();
            Log::channel('single')->error($e, [
                'Message' => 'Cannot connect to mailbox #'.$this->mailbox->getKey(),
                'xMailbox' => $this->mailbox->xMailbox,
                'transaction' => $this->transaction,
            ]);
            return false;
        }

        // Bail out if we have no messages.
        if ($box->messageCount() == 0) {
            Log::debug('['.get_class($this).'] no messages found in mailbox', [
                'xMailbox' => $this->mailbox->xMailbox,
                'sUsername' => $this->mailbox->sUsername,
                'sHostname' => $this->mailbox->sHostname,
                'transaction' => $this->transaction,
            ]);
            $this->mailbox->setMailboxAvailable();
            return false;
        }

        // Save each to the disk and push onto parsing queue
        foreach ($box->getMessages() as $mid) {
            if($mid > cHD_EMAILS_MAX_TO_IMPORT){
                Log::info("[".get_class($this)."]  Stopped importing email, reached max retrieval limit (".cHD_EMAILS_MAX_TO_IMPORT.")", [
                    'xMailbox' => $this->mailbox->xMailbox,
                    'sUsername' => $this->mailbox->sUsername,
                    'sHostname' => $this->mailbox->sHostname,
                    'transaction' => $this->transaction,
                ]);
                break;
            }

            $fileName = date('Y-m-d-His').'-'.Str::uuid()->toString().'.eml.gz';
            $transaction = Str::uuid()->toString();

            try {
                $content = $box->getMessage($mid);

                if (empty(trim($content))) {
                    Log::error(new \Exception('Email has no content'), [
                        'xMailbox' => $this->mailbox->xMailbox,
                        'filename' => $fileName,
                        'transaction' => $this->transaction,
                    ]);
                    continue;
                } else {
                    $stored = $this->storeEml($fileName, $content);
                }

            } catch (\Exception $e) {
                Log::error($e, [
                    'xMailbox' => $this->mailbox->xMailbox,
                    'filename' => $fileName,
                    'transaction' => $this->transaction,
                ]);
                continue;
            }

            if (! $stored) { // if it didn't store and no exception error out.
                Log::error('Failed to store eml', [
                    'xMailbox' => $this->mailbox->xMailbox,
                    'filename' => $fileName,
                    'transaction' => $this->transaction,
                ]);
                continue;
            }

            Log::debug('['.get_class($this).'] mail message eml stored, dispatching MailMessage', [
                'xMailbox' => $this->mailbox->xMailbox,
                'filename' => $fileName,
                'successful' => $stored,
                'mid' => $mid,
                'transaction' => $this->transaction,
                'child_transaction' => $transaction,
            ]);

            // Push onto queue to process
            MailMessage::dispatch($this->mailbox, $fileName, true, $transaction)
                ->onQueue(config('queue.high_priority_queue')); // mail.incoming

            // Should we delete or archive? Expunge needs
            // to be called in either case after it's done.
            if ($this->mailbox->fArchive == 1) {
                $expunged = $box->archive($mid);
            } else {
                $expunged = $box->delete($mid);
            }

            Log::debug('['.get_class($this).'] mail message marked for expunging', [
                'xMailbox' => $this->mailbox->xMailbox,
                'mid' => $mid,
                'successful' => $expunged,
                'transaction' => $this->transaction,
            ]);
        }

        $box->expunge();

        $this->mailbox->setMailboxAvailable();
        return true;
    }

    /**
     * @param $fileName
     * @param $contents
     * @return mixed
     */
    protected function storeEml($fileName, $contents)
    {
        return Storage::disk($this->storageDisk())
            ->put('mail/'.$fileName, gzencode($contents, 6));
    }

    public function visibleToAdministrators()
    {
        return true;
    }

    public function visibleMetaData()
    {
        return [
            'mailbox' => $this->mailbox->identify(),
            'transaction' => $this->transaction,
        ];
    }
}
