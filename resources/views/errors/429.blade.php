@extends('errors.layout', [
    'status' => '429',
    'title' => 'Muitas tentativas em pouco tempo',
    'message' => 'Por segurança, o sistema bloqueou novas tentativas por alguns instantes. Aguarde um pouco e tente novamente. Em caso de urgência, entre em contato.',
    'primaryUrl' => url('/admin/login'),
    'primaryLabel' => 'Voltar ao login',
])
