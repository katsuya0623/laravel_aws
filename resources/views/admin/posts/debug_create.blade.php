<!doctype html><meta charset="utf-8">
<h1>DEBUG: create plain</h1>
<form method="POST" action="{{ route('admin.posts.store') }}" enctype="multipart/form-data">
  @include('admin.posts._form', ['post'=>$post])
  <button type="submit">保存</button>
</form>
@includeIf('admin.posts._slug-script')
