@if($errors->any())
    <div class="alert alert-error">
        <i class="fas fa-circle-exclamation"></i>
        <div class="alert-msgs">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    </div>
@endif
