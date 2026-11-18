<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('inventory::modules.purchaseOrder.pdf.title', ['number' => $purchaseOrder->po_number]) }}</title>
    <style>
        @font-face {
            font-family: 'NotoSans';
            src: url('{{ public_path('fonts/NotoSans-Regular.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'NotoSans';
            src: url('{{ public_path('fonts/NotoSans-Bold.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        body {
            font-family: 'NotoSans', sans-serif;
            font-size: 14px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .restaurant-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .po-details {
            margin-bottom: 30px;
        }
        .po-details table {
            width: 100%;
        }
        .po-details td {
            padding: 5px;
            vertical-align: top;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f5f5f5;
        }
        .total {
            text-align: right;
            margin-top: 20px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="restaurant-name">{{ $purchaseOrder->branch->restaurant->name }}</div>
        <div>{{ $purchaseOrder->branch->name }}</div>
        <div>{{ $purchaseOrder->branch->address }}</div>
    </div>

    <h1 style="text-align: center;">{{ __('inventory::modules.purchaseOrder.pdf.heading') }}</h1>

    <div class="po-details">
        <table>
            <tr>
                <td width="50%">
                    <strong>{{ __('inventory::modules.purchaseOrder.supplier') }}:</strong><br>
                    {{ $purchaseOrder->supplier->name }}<br>
                    {{ $purchaseOrder->supplier->address }}<br>
                    {{ __('inventory::modules.purchaseOrder.pdf.phone') }}: {{ $purchaseOrder->supplier->phone }}<br>
                    {{ __('inventory::modules.purchaseOrder.pdf.email') }}: {{ $purchaseOrder->supplier->email }}
                </td>
                <td width="50%" style="text-align: right;">
                    <strong>{{ __('inventory::modules.purchaseOrder.po_number') }}:</strong> {{ $purchaseOrder->po_number }}<br>
                    <strong>{{ __('inventory::modules.purchaseOrder.order_date') }}:</strong> {{ $purchaseOrder->order_date->format('M d, Y') }}<br>
                    <strong>{{ __('inventory::modules.purchaseOrder.expected_delivery_date') }}:</strong> {{ $purchaseOrder->expected_delivery_date?->format('M d, Y') ?? __('inventory::modules.purchaseOrder.pdf.not_specified') }}<br>
                    <strong>{{ __('inventory::modules.purchaseOrder.pdf.status_label') }}:</strong> {{ __('inventory::modules.purchaseOrder.status.' . $purchaseOrder->status) }}
                </td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('inventory::modules.purchaseOrder.pdf.item') }}</th>
                <th>{{ __('inventory::modules.purchaseOrder.pdf.unit') }}</th>
                <th>{{ __('inventory::modules.purchaseOrder.quantity') }}</th>
                <th>{{ __('inventory::modules.purchaseOrder.unit_price') }}</th>
                <th>{{ __('inventory::modules.purchaseOrder.subtotal') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrder->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->inventoryItem->name }}</td>
                    <td>{{ $item->inventoryItem->unit->symbol }}</td>
                    <td>{{ number_format($item->quantity, 2) }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align: right;"><strong>{{ __('inventory::modules.purchaseOrder.pdf.total_amount') }}:</strong></td>
                <td><strong>{{ number_format($purchaseOrder->total_amount, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    @if($purchaseOrder->notes)
        <div style="margin-bottom: 30px;">
            <strong>{{ __('inventory::modules.purchaseOrder.notes') }}:</strong><br>
            {{ $purchaseOrder->notes }}
        </div>
    @endif

    <div style="margin-top: 50px;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 33%; text-align: center;">
                    _______________________<br>
                    {{ __('inventory::modules.purchaseOrder.pdf.prepared_by') }}<br>
                    {{ $purchaseOrder->createdBy->name }}
                </td>
                <td style="width: 33%; text-align: center;">
                    _______________________<br>
                    {{ __('inventory::modules.purchaseOrder.pdf.approved_by') }}
                </td>
                <td style="width: 33%; text-align: center;">
                    _______________________<br>
                    {{ __('inventory::modules.purchaseOrder.pdf.received_by') }}
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>{{ __('inventory::modules.purchaseOrder.pdf.footer_note') }}</p>
    </div>
</body>
</html> 