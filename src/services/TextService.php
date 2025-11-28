<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';


function autoLinkContact(string $text, $pdo): string
{
    
    
    static $encryptedEmail = null;


        $stmt = $pdo->prepare("SELECT email FROM nanook_admin_users WHERE id = 2");
        $stmt->execute();
        $email = $stmt->fetchColumn();

        if ($email) {
            
            $encryptedEmail = str_rot13($email);
        } else {
            
            $encryptedEmail = '';
        }

    if (empty($encryptedEmail)) {
        return $text;
    }

    
    
    
    $pattern = '/\b(contactez[\s-]?moi|contacter|contact)\b/iu';

    
    
    return preg_replace_callback($pattern, function($matches) use ($encryptedEmail) {
        
        
        return '<a href="#" class="mailme" data-enc="' . htmlspecialchars($encryptedEmail) . '">' . $matches[0] . '</a>';
    }, $text);
}