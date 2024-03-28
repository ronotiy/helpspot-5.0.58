<?php


namespace HS\Mail;


interface Attachment
{
    public function isEmbed();

    public function toSwift();

    public function persist();

    public function cleanup();
}
