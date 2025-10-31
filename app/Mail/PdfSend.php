<?php

namespace App\Mail;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PdfSend extends Mailable //implements ShouldQueue
{
    use Queueable, SerializesModels;

    private $form;
    /**
     * Create a new message instance.
     */
    public function __construct($form)
    {
        $this->form = $form;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->form['document'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.pdf-send',
            with: [
                'form' => $this->form,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $pdf = Pdf::loadView($this->form['view_pdf_patch'], [
            'model' => $this->form['model'],
        ])->output();
        return [
            Attachment::fromData(fn() => $pdf, 'document.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
