<?php

namespace HS\Charset\Encoder;

use HS\Charset\Encoder\Filter\FilterInterface;

class Manager
{
    /**
     * Collection of encoder handlers.
     *
     * @var array
     */
    protected $handlers = [];

    /**
     * Collection of filters, to br
     * run before handlers.
     */
    protected $preFilters = [];

    /**
     * Collection of filters, to br
     * run after handlers.
     */
    protected $postFilters = [];

    /**
     * Add an encoder handler.
     *
     * @param \HS\Charset\Encoder\HandlerInterface
     */
    public function addHandler(HandlerInterface $handler)
    {
        $this->handlers[] = $handler;
    }

    /**
     * Add a filter to be run before handlers.
     *
     * @param \HS\Charset\Encoder\Filter\FilterInterface
     */
    public function addPreFilter(FilterInterface $filter)
    {
        $this->preFilters[] = $filter;
    }

    /**
     * Add a filter to be run after handlers.
     *
     * @param \HS\Charset\Encoder\Filter\FilterInterface
     */
    public function addPostFilter(FilterInterface $filter)
    {
        $this->postFilters[] = $filter;
    }

    /**
     * Run input through all encoders.
     * Chain of Responsibility: First encoder to successfully
     * encode returns the used result.
     *
     * @param  string  The string to encode
     * @param  string  The charset of $string
     * @param  string  The desired charset of output
     * @return string  Encoded string
     */
    public function encode($string, $to, $from)
    {
        if (empty($string)) {
            return $string;
        }

        // Run Pre-Filters
        foreach ($this->preFilters as $filter) {
            $string = $filter->filter($string);
        }

        /**
         * Return result of the first encoder to
         * successfully encode.
         */
        $output = null;
        foreach ($this->handlers as $handler) {
            $output = $handler->encode($string, $to, $from);

            // If not empty, null nor false
            //   break out of foreach loop
            if ($output) {
                break;
            }
        }

        // Run Post-Filters
        foreach ($this->postFilters as $filter) {
            $output = $filter->filter($output);
        }

        return $output;
    }
}
