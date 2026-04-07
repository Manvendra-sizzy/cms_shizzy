<div class="field">
    <label>Name</label>
    <input name="name" value="{{ old('name', $category->name ?? '') }}" required>
</div>
<div class="field">
    <label>Description</label>
    <textarea name="description">{{ old('description', $category->description ?? '') }}</textarea>
</div>
<label style="display:flex;gap:8px;align-items:center;">
    <input type="checkbox" name="active" value="1" style="width:auto;" @checked(old('active', $category->active ?? true))>
    Active
</label>

