{{-- Flash global mengapung untuk semua halaman mobile --}}
@if(session('success') || session('error') || $errors->any())
    <div class="mo-flash-stack">
        @if(session('success'))
            <div class="mo-toast mo-toast--success">
                <i class="fas fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mo-toast mo-toast--error">
                <i class="fas fa-circle-exclamation"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="mo-toast mo-toast--error">
                <i class="fas fa-triangle-exclamation"></i>
                <div>
                    <strong>Periksa kembali input Anda</strong>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>
@endif
