<?php

namespace HS\Mail;


use Illuminate\Support\Collection;

class Attachments extends Collection
{
    /**
     * @param array $helpspotDocs
     * @return Attachments
     */
    public static function parse($helpspotDocs=[]) {

        $attachments = new static([]);

        foreach (($helpspotDocs['error'] ?? []) as $key => $error) {
            if (! empty($helpspotDocs['name'][$key])) {
                if ($error == UPLOAD_ERR_OK) {
                    if (isset($helpspotDocs['xDocumentId'][$key]) && ! empty($helpspotDocs['xDocumentId'][$key])) {
                        $isEmbed = isset($helpspotDocs['is_inline'][$key]) && $helpspotDocs['is_inline'][$key] == true;
                        $attachments->push(new HelpspotAttachment($helpspotDocs['xDocumentId'][$key], $isEmbed));
                    } else {
                        // If we don't have an xDocumentId for some reason, use the UploadedAttachment
                        // This should not end up being used, but we're keeping it here just in case.
                        // This should get removed after testing. If you're reading this years later - Hi, welcome to Real Code™
                        if (isset($helpspotDocs['is_inline'][$key]) && $helpspotDocs['is_inline'][$key] == true) {
                            $attachments->push(new UploadedAttachment(
                                $path=$helpspotDocs['tmp_name'][$key],
                                $fileName=$helpspotDocs['name'][$key],
                                $contentType=$helpspotDocs['type'][$key],
                                $cid=$helpspotDocs['content-id'][$key]
                            ));
                        } else {
                            $attachments->push(new UploadedAttachment(
                                $path=$helpspotDocs['tmp_name'][$key],
                                $fileName=$helpspotDocs['name'][$key],
                                $contentType=$helpspotDocs['type'][$key]
                            ));
                        }
                    }
                }
            }
        }

        return $attachments;
    }

    public function persist()
    {
        $this->each(function(Attachment $attachment) {
            $attachment->persist();
        });

        return $this;
    }

    public function cleanup()
    {
        $this->each(function(Attachment $attachment) {
            $attachment->cleanup();
        });

        return $this;
    }
}
