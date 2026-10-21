@extends('layouts.app')

@section('title', 'Qui sommes-nous ? - '.config('app.name'))

@push('styles')
<style>
    .nous-hero {
        min-height: 60vh;
        display: flex;
        align-items: center;
        text-align: center;
        color: #fff;
        border-radius: 18px;
        background: linear-gradient(rgba(0, 0, 0, .55), rgba(0, 0, 0, .55)),
            url('https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?auto=format&fit=crop&w=1600&q=70') center/cover;
    }
    .nous-hero h1 { font-size: clamp(30px, 6vw, 44px); font-weight: 800; margin-bottom: 10px; }
    .nous-hero p { font-size: 18px; opacity: .95; }
    .nous-hero .btn-vision {
        display: inline-block;
        margin-top: 24px;
        padding: 14px 32px;
        background: var(--primary);
        color: #fff;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 600;
        transition: .3s;
    }
    .nous-hero .btn-vision:hover { background: var(--primary-dark); color: #fff; }
    .nous-about h3 { font-size: 26px; font-weight: 700; margin-bottom: 15px; }
    .nous-about p { color: #555; }
    .nous-list { padding: 0; margin: 0; list-style: none; }
    .nous-list li { margin-bottom: 14px; font-size: 15px; display: flex; align-items: flex-start; }
    .nous-list i { color: var(--primary); font-size: 18px; margin-right: 8px; }
    .nous-features { margin-top: 50px; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px; }
    .nous-box { background: #fff; padding: 35px 25px; border-radius: 18px; box-shadow: 0 15px 40px rgba(0, 0, 0, .07); transition: .3s; }
    .nous-box:hover { transform: translateY(-6px); }
    .nous-box .icon { font-size: 42px; color: var(--primary); margin-bottom: 12px; }
    .nous-box h4 { font-size: 18px; margin-bottom: 8px; }
    .nous-box p { font-size: 14px; color: #6c757d; margin: 0; }
    .nous-cta {
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        color: #fff;
        text-align: center;
        border-radius: 18px;
        padding: 60px 20px;
    }
    .nous-cta h3 { font-size: 30px; font-weight: 800; }
    .nous-cta p { margin: 10px 0 0; font-size: 16px; opacity: .95; }
</style>
@endpush

@section('content')
<div class="container py-4">
    <section class="nous-hero mb-5">
        <div class="container">
            <h1>{{ config('legal.slogan') }}</h1>
            <p>La plateforme qui donne une seconde vie à vos biens</p>
            <a href="#about" class="btn-vision">Découvrir notre vision</a>
        </div>
    </section>

    <section id="about" class="nous-about mb-5">
        <div class="row g-5">
            <div class="col-md-6">
                <h3>Pourquoi choisir la seconde main ?</h3>
                <p>Acheter ou vendre d'occasion est aujourd'hui un choix intelligent, économique et responsable pour vous et pour l'environnement.</p>
            </div>
            <div class="col-md-6">
                <ul class="nous-list">
                    <li><i class="bx bx-check-double"></i> Dépenser moins pour des produits de qualité</li>
                    <li><i class="bx bx-check-double"></i> Générer des revenus avec ses anciens biens</li>
                    <li><i class="bx bx-check-double"></i> Réduire le gaspillage et préserver la planète</li>
                </ul>
            </div>
        </div>

        <div class="nous-features">
            <div class="nous-box">
                <div class="icon"><i class="bx bx-wallet"></i></div>
                <h4>Économiser</h4>
                <p>Accédez à des produits fiables à prix réduits</p>
            </div>
            <div class="nous-box">
                <div class="icon"><i class="bx bx-line-chart"></i></div>
                <h4>Gagner</h4>
                <p>Transformez vos anciens biens en argent</p>
            </div>
            <div class="nous-box">
                <div class="icon"><i class="bx bx-leaf"></i></div>
                <h4>Préserver</h4>
                <p>Agissez pour une consommation responsable</p>
            </div>
        </div>
    </section>

    <section class="nous-contact mb-5">
        <h4 class="fw-bold mb-3">Nous contacter</h4>
        <p class="mb-1"><i class="bx bx-map"></i> {{ config('legal.address') }}</p>
        @if(config('legal.public_phone'))
        <p class="mb-1"><i class="bx bx-phone"></i> <a href="tel:{{ preg_replace('/\s+/', '', config('legal.public_phone')) }}" class="text-decoration-none">{{ config('legal.public_phone') }}</a></p>
        @endif
        @if(config('legal.public_email'))
        <p class="mb-1"><i class="bx bx-envelope"></i> <a href="mailto:{{ config('legal.public_email') }}" class="text-decoration-none">{{ config('legal.public_email') }}</a></p>
        @endif
        <p class="mb-0"><i class="bx bx-message-dots"></i> <a href="{{ route('contact') }}" class="text-decoration-none">Formulaire de contact</a></p>
    </section>

    <section class="nous-cta">
        <h3>Consommer autrement, c'est possible</h3>
        <p>{{ config('app.name') }} – La plateforme des plus malins</p>
    </section>
</div>
@endsection
