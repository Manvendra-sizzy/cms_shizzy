<?php

namespace App\Mail;

use App\Modules\HRMS\Documents\Models\HRDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class HrDocumentIssuedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public HRDocument $document)
    {
    }

    public function build(): self
    {
        $type = $this->document->typeLabel();
        $this->subject("Document issued: {$type}")
            ->view('emails.cms.document_issued', [
                'document' => $this->document,
                'type' => $type,
            ]);

        $path = (string) ($this->document->file_path ?? '');
        if ($path !== '' && Storage::disk('local')->exists($path)) {
            $bytes = Storage::disk('local')->get($path);
            if (is_string($bytes) && $bytes !== '') {
                $this->attachData($bytes, 'document-'.$this->document->id.'.pdf', [
                    'mime' => 'application/pdf',
                ]);
            }
        }

        return $this;
    }
}

