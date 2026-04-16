<div class="field">
    <label>Name</label>
    <input name="name" value="{{ old('name', $client->name ?? '') }}" required>
</div>
<div class="form-grid cols-2">
    <div class="field">
        <label>Email</label>
        <input name="email" value="{{ old('email', $client->email ?? '') }}">
    </div>
    <div class="field">
        <label>Phone</label>
        <input name="phone" value="{{ old('phone', $client->phone ?? '') }}">
    </div>
</div>
<div class="field">
    <label>Address</label>
    <textarea name="address">{{ old('address', $client->address ?? '') }}</textarea>
</div>
<label style="display:flex;gap:8px;align-items:center;">
    <input type="checkbox" name="active" value="1" style="width:auto;" @checked(old('active', ($client->active ?? true)))>
    Active
</label>

