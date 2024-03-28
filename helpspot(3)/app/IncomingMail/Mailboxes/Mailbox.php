<?php

namespace HS\IncomingMail\Mailboxes;

interface Mailbox
{
    /**
     * @return mixed
     */
    public function messageCount();

    /**
     * @param $id
     * @return mixed
     */
    public function getMessage($id);

    /**
     * @param $id
     * @return mixed
     */
    public function delete($id);

    /**
     * @return mixed
     */
    public function expunge();

    /**
     * @return array
     */
    public function getMessages();
}
