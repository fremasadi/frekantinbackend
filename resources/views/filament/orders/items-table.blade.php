<div class="overflow-x-auto">
    <table class="w-full">
        <thead>
            <tr>
                <th class="text-left p-2">Produk</th>
                <th class="text-left p-2">Jumlah</th>
                <th class="text-left p-2">Harga</th>
                <th class="text-left p-2">Subtotal</th>
                {{-- <th class="text-left p-2">Catatan</th> --}}
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td class="p-2">{{ $item->product->name }}</td>
                    <td class="p-2">{{ $item->quantity }}</td>
                    <td class="p-2">Rp {{ number_format($item->price, 2, ',', '.') }}</td>
                    <td class="p-2">Rp {{ number_format($item->price * $item->quantity, 2, ',', '.') }}</td>
                    {{-- <td class="p-2">{{ $item->notes ?? '-' }}</td> --}}
                </tr>
            @endforeach
        </tbody>
    </table>
</div>