<?php

namespace App\Mail;

use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShareShoppingListMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ShoppingList $shoppingList,
        public User $sender,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->sender->name.' shared a shopping list with you',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.shopping-list-shared',
            with: [
                'url' => url("/shared/shopping-list/{$this->shoppingList->share_token}"),
                'senderName' => $this->sender->name,
                'listName' => $this->shoppingList->mealPlan?->name ?? 'Shopping List',
            ],
        );
    }
}
