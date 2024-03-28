<?php

namespace HS\IncomingMail\Jobs;

use HS\Mailbox;
use HS\Cloud\IsHosted;
use HS\Jobs\CleanMailFile;
use HS\Jobs\AdministratesJobs;

use Illuminate\Bus\Queueable;
use HS\IncomingMail\Mailman\Parse;
use HS\IncomingMail\Mailman\Deliver;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use HS\IncomingMail\Loggers\EchoLogger;
use Illuminate\Support\Facades\Storage;
use HS\IncomingMail\Loggers\DummyLogger;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class MailMessage extends MailJobBase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, AdministratesJobs, IsHosted;

    protected string $jobName = 'Parse Raw Email';
    protected string $jobCategory = 'mail.incoming';

    /**
     * @var string
     */
    private string $fileName;

    /**
     * @var Mailbox
     */
    private Mailbox $mailbox;

    /**
     * @var bool
     */
    private bool $debug;

    /**
     * @var string
     */
    private string $transaction;

    /**
     * MailProcessor constructor.
     *
     * @param Mailbox $mailbox
     * @param $fileName
     * @param bool $debug
     * @param string $transaction
     */
    public function __construct(Mailbox $mailbox, $fileName, $debug = false, $transaction = '')
    {
        $this->mailbox = $mailbox;
        $this->fileName = $fileName;
        $this->debug = $debug;
        $this->transaction = $transaction;
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff()
    {
        return [
            60,
            60 * 10, // 10 minutes
            60 * 60 * 2, // 2 hours
            60 * 60 * 4, // 4 hours
            60 * 60 * 8, // 8 hours
            60 * 60 * 24, // 24 hours
        ];
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $this->loadTheWorld();
        $eml = gzdecode(Storage::disk($this->storageDisk())
            ->get('mail/'.$this->fileName));

        Log::debug('['.get_class($this).'] eml file retrieved', [
            'xMailbox' => $this->mailbox->xMailbox,
            'filename' => $this->fileName,
            'successfully_uncompressed' => is_string($eml),
            'transaction' => $this->transaction,
        ]);

        $parser = new Parse($eml, 'new', $this->transaction);
        $message = $parser->decode();

        Log::debug('['.get_class($this).'] eml file parsed', [
            'xMailbox' => $this->mailbox->xMailbox,
            'filename' => $this->fileName,
            'attachment_count' => count($message->attachments),
            'inline_attachment_count' => count($message->inline_attachments),
            'successfully_decoded' => is_object($message),
            'transaction' => $this->transaction,
        ]);

        $deliver = new Deliver($this->mailbox, $this->getLogger(), $this->transaction);

        $deliver->toDb($message);
        Log::debug('['.get_class($this).'] email message delivered', [
            'xMailbox' => $this->mailbox->xMailbox,
            'filename' => $this->fileName,
            'transaction' => $this->transaction,
        ]);

        if ($this->storageDisk() == 'local') {
            CleanMailFile::dispatch($this->fileName)
                ->delay(now()->addDays(hs_setting('cHD_SAVED_MAIL_CLEANUP_DELAY', 5))); // mail.incoming
        }
    }

    /**
     * Get the logger based on debug flag.
     *
     * @return DummyLogger|EchoLogger
     */
    protected function getLogger()
    {
        if ($this->debug) { // print to screen
            return new EchoLogger($this->fileName);
        }

        return new DummyLogger($this->fileName);
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
