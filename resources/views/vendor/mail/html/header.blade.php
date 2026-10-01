@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ asset('assets/img/icon.png') }}" class="logo" alt="{{ config('app.name') }}" style="border-radius: 50%;">
<br>
{!! $slot !!}
</a>
</td>
</tr>
