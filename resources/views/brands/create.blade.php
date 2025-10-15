{{-- Minimal stub view for brands.create --}}
<div>
    <h1>Create Brand</h1>
    <form method="POST" action="/brands">
        @csrf
        <input type="text" name="name" placeholder="Name" />
        <input type="text" name="slug" placeholder="Slug" />
        <input type="text" name="website_url" placeholder="Website URL" />
        <input type="text" name="logo_url" placeholder="Logo URL" />
        <textarea name="description" placeholder="Description"></textarea>
        <button type="submit">Save</button>
    </form>
</div>
