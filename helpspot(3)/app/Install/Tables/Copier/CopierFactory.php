<?php

namespace HS\Install\Tables\Copier;

use HS\Charset\Encoder\Manager;
use HS\Html\Clean\CleanerInterface;

class CopierFactory
{
    /**
     * @var Manager
     */
    private $encoder;

    /**
     * @var CleanerInterface
     */
    private $cleaner;

    public function __construct(Manager $encoder, CleanerInterface $cleaner)
    {
        $this->encoder = $encoder;
        $this->cleaner = $cleaner;
    }

    public function basic($srcConn, $destConn, $encodeFrom, $encodeTo)
    {
        return new BasicCopier($srcConn, $destConn, $this->cleaner, $this->encoder, $encodeFrom, $encodeTo);
    }

    public function chunked($srcConn, $destConn, $encodeFrom, $encodeTo)
    {
        return new ChunkedCopier($srcConn, $destConn, $this->cleaner, $this->encoder, $encodeFrom, $encodeTo);
    }

    public function paginated($srcConn, $destConn, $encodeFrom, $encodeTo)
    {
        return new PaginatedCopier($srcConn, $destConn, $this->cleaner, $this->encoder, $encodeFrom, $encodeTo);
    }
}
