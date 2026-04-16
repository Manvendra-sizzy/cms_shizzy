<div class="form-grid cols-2">
    <div class="field">
        <label>Category</label>
        <select name="asset_category_id" required>
            <option value="">Select category</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" @selected(old('asset_category_id', $asset->asset_category_id ?? null) == $cat->id)>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label>Status</label>
        <select name="status">
            @php $statusValue = old('status', $asset->status ?? 'in_stock'); @endphp
            @foreach(['in_stock','assigned','retired','lost'] as $st)
                <option value="{{ $st }}" @selected($statusValue === $st)>{{ $st }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="field">
    <label>Name</label>
    <input name="name" value="{{ old('name', $asset->name ?? '') }}" required>
</div>

<div class="form-grid cols-2">
    <div class="field">
        <label>Condition</label>
        <input name="condition" value="{{ old('condition', $asset->condition ?? '') }}" placeholder="e.g. New, Good, Fair">
    </div>
    <div class="field">
        <label>Asset code</label>
        <input name="asset_code" value="{{ old('asset_code', $asset->asset_code ?? '') }}" placeholder="Internal ID (optional)">
    </div>
</div>

<div class="form-grid cols-2">
    <div class="field">
        <label>Serial number</label>
        <input name="serial_number" value="{{ old('serial_number', $asset->serial_number ?? '') }}">
    </div>
    <div class="field">
        <label>Purchase date</label>
        <input type="date" name="purchase_date" value="{{ old('purchase_date', optional($asset->purchase_date ?? null)->format('Y-m-d')) }}">
    </div>
</div>

<div class="field">
    <label>Purchase value</label>
    <input type="number" step="0.01" min="0" name="purchase_value" value="{{ old('purchase_value', $asset->purchase_value ?? '') }}">
</div>

<div class="field">
    <label>Description</label>
    <textarea name="description">{{ old('description', $asset->description ?? '') }}</textarea>
</div>

