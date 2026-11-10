@php
    $segments = request()->segments();
    $mobileSegment = array_search('m', $segments, true);
    $mobileToken = $mobileSegment !== false ? ($segments[$mobileSegment + 1] ?? null) : null;
@endphp

@extends('errors.layout', [
    'status' => '419',
    'title' => 'Sessão expirada',
    'message' => 'Por segurança, sua sessão expirou. Entre novamente para continuar.',
    'primaryUrl' => $mobileToken ? route('maquinas.public.show', $mobileToken) : url('/admin/login'),
    'primaryLabel' => $mobileToken ? 'Voltar ao QR Code' : 'Acessar sistema',
    'autoRedirectUrl' => $mobileToken ? route('maquinas.public.show', $mobileToken) : null,
])
