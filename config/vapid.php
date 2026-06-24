<?php
/**
 * =====================================================================
 *  VAPID anahtarları (Web Push)
 * =====================================================================
 *  Bu dosya gizlidir (config/.htaccess ile web erişimine kapalıdır).
 *  Anahtarlar uygulamaya özeldir; değiştirmeyin (değiştirirseniz tüm
 *  mevcut bildirim abonelikleri geçersiz olur ve kullanıcıların yeniden
 *  izin vermesi gerekir).
 *
 *  - VAPID_PUBLIC_KEY : tarayıcıya verilen genel anahtar (base64url).
 *  - VAPID_PRIVATE_PEM: sunucuda JWT imzalamak için özel anahtar (PEM).
 *  - VAPID_SUBJECT    : iletişim adresi (mailto: veya https URL).
 * =====================================================================
 */

define('VAPID_PUBLIC_KEY', 'BLHsAtE8RJXGb3ZRq9dWfSA_YOatWNL7aq0DUiwB2MfWy365QZMesWJ6WB7_koqxD1RhRRZdVMRl0-hoicQu8Pw');

define('VAPID_PRIVATE_PEM', "-----BEGIN EC PRIVATE KEY-----\n"
    . "MHcCAQEEIKgFaQjU8wFrRk1GSBoTteP+YuhHGDimRriSyMEEdfVFoAoGCCqGSM49\n"
    . "AwEHoUQDQgAEsewC0TxElcZvdlGr11Z9ID9g5q1Y0vtqrQNSLAHYx9bLfrlBkx6x\n"
    . "YnpYHv+SirEPVGFFFl1UxGXT6GiJxC7w/A==\n"
    . "-----END EC PRIVATE KEY-----\n");

// Bildirim göndereni tanımlayan iletişim adresi. Kendi alan adınızla değiştirin.
define('VAPID_SUBJECT', 'mailto:berkanpekta44@gmail.com');
