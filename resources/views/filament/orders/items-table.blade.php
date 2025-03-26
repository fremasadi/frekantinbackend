<div class="overflow-x-auto">
    <table class="w-full">
        <thead>
            <tr>
                <th class="text-left">Produk</th>
                <th class="text-left">Jumlah</th>
                <th class="text-left">Harga</th>
                <th class="text-left">Subtotal</th>
                <th class="text-left">Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->price, 2) }}</td>
                    <td>{{ number_format($item->price * $item->quantity, 2) }}</td>
                    <td>{{ $item->notes }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>