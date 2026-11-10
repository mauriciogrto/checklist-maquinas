@extends('errors.layout', [
    'status' => '502',
    'title' => 'Falha de comunicação',
    'message' => 'O servidor recebeu uma resposta inválida ao tentar carregar o sistema. Aguarde alguns instantes e tente novamente.',
])
