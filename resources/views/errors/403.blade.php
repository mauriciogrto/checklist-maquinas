@extends('errors.layout', [
    'status' => '403',
    'title' => 'Acesso não autorizado',
    'message' => 'Seu usuário não tem permissão para abrir esta área do sistema.',
])
