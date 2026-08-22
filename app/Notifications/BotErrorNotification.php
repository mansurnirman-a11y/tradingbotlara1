<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\BotInstance;

class BotErrorNotification extends Notification
{

    protected $bot;
    protected $errorMessage;

    public function __construct(BotInstance $bot, string $errorMessage)
    {
        $this->bot = $bot;
        $this->errorMessage = $errorMessage;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database']; // Added database for in-app bell notifications
    }

    public function toArray(object $notifiable): array
    {
        return [
            'bot_id' => $this->bot->id,
            'bot_name' => $this->bot->name,
            'symbol' => $this->bot->symbol,
            'error_message' => $this->formatErrorMessage($this->errorMessage),
            'solution' => $this->analyzeErrorForSolution($this->errorMessage)
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $solution = $this->analyzeErrorForSolution($this->errorMessage);
        $botName = $this->bot->name ?? 'Trading Bot';

        return (new MailMessage)
                    ->error()
                    ->subject("Bot Error Alert: {$botName}")
                    ->greeting("Hello {$notifiable->name},")
                    ->line("Your trading bot **{$botName}** encountered a critical error and could not execute its trade.")
                    ->line("**Trading Pair:** {$this->bot->symbol}")
                    ->line("**Error Message:**")
                    ->line($this->formatErrorMessage($this->errorMessage))
                    ->line("**Suggested Solution:**")
                    ->line("👉 " . $solution)
                    ->action('Manage Your Bots', config('app.url') . '/bots')
                    ->line('Please fix the issue to resume automated trading.');
    }

    private function analyzeErrorForSolution(string $error): string
    {
        $error = strtolower($error);

        if (str_contains($error, 'insufficient_margin') || str_contains($error, 'insufficient funds') || str_contains($error, 'margin')) {
            return "Your account does not have enough margin/balance to place this order. Please increase your leverage on the exchange or add more funds to your wallet.";
        }

        if (str_contains($error, 'negative_order_size') || str_contains($error, 'precision') || str_contains($error, 'too small')) {
            return "Your allocated capital is too small to meet the exchange's minimum order size requirements for this coin. Please increase the 'Allocated Capital' in your bot settings.";
        }

        if (str_contains($error, 'api key') || str_contains($error, 'unauthorized') || str_contains($error, 'signature') || str_contains($error, 'invalid api')) {
            return "Your Broker API keys are invalid or have expired. Please go to the 'Connect Broker' page and update your API credentials.";
        }

        if (str_contains($error, 'bad symbol') || str_contains($error, 'not supported')) {
            return "The selected trading pair is either not supported by your broker or spelled incorrectly. Please recreate the bot with a valid symbol (e.g. BTC/USDT or BTC/USD).";
        }

        return "Please review your bot settings and ensure your exchange account is properly configured.";
    }

    private function formatErrorMessage(string $error): string
    {
        // Truncate very long CCXT error messages so the email looks clean
        if (strlen($error) > 150) {
            return substr($error, 0, 147) . '...';
        }
        return $error;
    }
}
