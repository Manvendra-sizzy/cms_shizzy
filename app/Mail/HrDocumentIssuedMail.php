<?php

namespace App\Mail;

use App\Modules\HRMS\Documents\Models\HRDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HrDocumentIssuedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public HRDocument $document)
    {
    }

    public function build(): self
    {
        $type = $this->document->type ? str_replace('_', ' ', ucwords($this->document->type, '_')) : 'Document';
        return $this
            ->subject("Document issued: {$type}")
            ->view('emails.cms.document_issued', [
                'document' => $this->document,
                'type' => $type,
            ]);
    }
}

