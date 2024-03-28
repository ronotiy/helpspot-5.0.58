<?php

namespace HS\IncomingMail\Mailman;

use Exception;
use HS\IncomingMail\Message;
use HS\IncomingMail\Parsers\MimeDecode;
use HS\IncomingMail\Parsers\MailMimeParser;

use Illuminate\Support\Facades\Log;

class Parse
{
    private $eml;

    /**
     * @var string
     */
    private $parser;

    /**
     * @var string
     */
    private $transaction;

    /**
     * Parse constructor.
     * @param $eml
     * @param string $parser
     * @param string $transaction
     */
    public function __construct($eml, $parser = 'new', $transaction = '')
    {
        $this->eml = $eml;
        $this->parser = $parser;
        $this->transaction = $transaction;
    }

    /**
     * @return bool|Message|object
     */
    public function decode()
    {
        if ($this->parser == 'new') {
            $decoder = new MailMimeParser(new Message(), $this->transaction);
        } else {
            $decoder = new MimeDecode(new Message(), $this->transaction);
        }

        try {
            $result = $decoder->parse($this->eml, $this->transaction);

            Log::debug('['.get_class($this).'] eml file being parsed', [
                'parser' => get_class($decoder),
                'parsed_return_value_type' => gettype($result),
                'transaction' => $this->transaction,
            ]);

            return $result;
        } catch (Exception $e) {
            errorLog('Parsing EML Failed ' . $e->getMessage());
            Log::error($e);
            return false;
        }
    }
}
