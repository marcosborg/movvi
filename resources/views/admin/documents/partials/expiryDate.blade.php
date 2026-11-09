<div class="form-group {{ $errors->has($field) ? 'has-error' : '' }}">
    <label for="{{ $field }}">{{ $label }}</label>
    <input
        class="form-control date"
        type="text"
        name="{{ $field }}"
        id="{{ $field }}"
        value="{{ old($field, isset($document) ? $document->{$field} : '') }}"
        placeholder="{{ config('panel.date_format') }}"
    >
    @if($errors->has($field))
        <span class="help-block" role="alert">{{ $errors->first($field) }}</span>
    @endif
</div>
