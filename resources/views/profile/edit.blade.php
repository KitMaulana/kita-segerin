<x-app-layout title="Profil Saya" subtitle="Ubah nama dan kata sandi Anda">

    <div class="mx-auto max-w-2xl space-y-5">

        <section class="rounded-2xl border border-berry/10 bg-white p-5 shadow-sm sm:p-6">
            @include('profile.partials.update-profile-information-form')
        </section>

        <section class="rounded-2xl border border-berry/10 bg-white p-5 shadow-sm sm:p-6">
            @include('profile.partials.update-password-form')
        </section>

        <section class="rounded-2xl border border-berry/10 bg-frost p-5 text-sm text-ink/60">
            <p class="font-bold text-ink">Butuh akun baru atau ganti peran?</p>
            <p class="mt-1 leading-relaxed">
                Penambahan dan penonaktifan akun dilakukan pemilik usaha lewat menu
                <span class="font-semibold">Akun Admin</span>.
            </p>
        </section>

    </div>

</x-app-layout>
