<?php

class Mailer
{
    private string $apiKey = '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';
    private string $senderEmail = 'contact@nanook.paris';
    private string $senderName = 'Nanook';
    private string $endpoint = 'https://api.brevo.com/v3/smtp/email';

    public function send(string $toEmail, string $subject, string $htmlBody, ?string $replyToEmail = null): bool
    {
        $payload = [
            'sender' => [
                'email' => $this->senderEmail,
                'name'  => $this->senderName,
            ],
            'to' => [
                ['email' => $toEmail],
            ],
            'subject'     => $subject,
            'htmlContent' => $htmlBody,
        ];

        if ($replyToEmail !== null) {
            $payload['replyTo'] = $replyToEmail;
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->endpoint,
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'accept: application/json',
                'content-type: application/json',
                'api-key: ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false || $httpCode >= 400) {
            error_log('Brevo API error: HTTP ' . $httpCode . ' - ' . curl_error($ch) . ' - ' . $response);
            curl_close($ch);
            return false;
        }

        curl_close($ch);
        return true;
    }
}
