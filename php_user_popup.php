<?php
function munch_show_user_popup($message, $backUrl = '') {
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $safeBack = htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8');
    $buttonAction = $safeBack !== '' ? "window.location.href='{$safeBack}'" : "window.history.back()";
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Munch Notice</title><link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'><link rel='stylesheet' href='style.css'><link rel='stylesheet' href='auth.css'><link rel='stylesheet' href='munch-clean-ui.css'><style>body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#EAF2F5;padding:1.5rem}.munch-server-popup{width:min(440px,100%);background:#fff;border-radius:1rem;padding:2rem;text-align:center;box-shadow:0 1.2rem 2.6rem rgba(0,0,0,.18);border:1px solid rgba(0,118,118,.16)}.munch-server-popup i{font-size:2.4rem;color:#007676;margin-bottom:1rem}.munch-server-popup h1{font-size:1.55rem;color:#007676;margin-bottom:.8rem}.munch-server-popup p{line-height:1.65;color:#36524f;margin-bottom:1.4rem}.munch-server-popup button{border:none;border-radius:999px;padding:.85rem 1.5rem;background:#007676;color:#fff;font-weight:800;cursor:pointer}</style></head><body><div class='munch-server-popup'><i class='fa-solid fa-circle-info'></i><h1>Please check this</h1><p>{$safeMessage}</p><button onclick=\"{$buttonAction}\">Go Back</button></div></body></html>";
    exit();
}
?>
