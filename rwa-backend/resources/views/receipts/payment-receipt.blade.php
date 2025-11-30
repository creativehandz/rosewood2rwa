<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - {{ $receipt->receipt_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 14px;
            line-height: 1.4;
            color: #333;
            background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIj48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZm9udC1zaXplPSI0MHB4IiBmaWxsPSJyZ2JhKDAsMCwwLDAuMDUpIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iMC4zZW0iIHRyYW5zZm9ybT0icm90YXRlKC00NSA1MCUgNTAlKSI+UkVDRUlQVDwvdGV4dD48L3N2Zz4=') repeat;
        }

        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border: 2px solid #333;
            border-radius: 10px;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }

        .header p {
            margin: 5px 0 0;
            font-size: 16px;
            opacity: 0.9;
        }

        .receipt-info {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
        }

        .receipt-info h2 {
            margin: 0 0 10px;
            color: #495057;
            font-size: 18px;
        }

        .info-grid {
            display: table;
            width: 100%;
        }

        .info-row {
            display: table-row;
        }

        .info-cell {
            display: table-cell;
            padding: 8px 0;
            vertical-align: top;
        }

        .info-cell:first-child {
            font-weight: bold;
            width: 200px;
            color: #495057;
        }

        .content {
            padding: 20px;
        }

        .payment-details {
            margin-bottom: 30px;
        }

        .payment-details h3 {
            color: #495057;
            border-bottom: 2px solid #667eea;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }

        .amount-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .total-amount {
            font-size: 20px;
            font-weight: bold;
            color: #28a745;
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 5px;
            border: 2px solid #28a745;
            margin-top: 15px;
        }

        .amount-words {
            text-align: center;
            font-style: italic;
            color: #6c757d;
            margin-top: 10px;
            padding: 10px;
            background: #e9ecef;
            border-radius: 5px;
        }

        .signatures {
            margin-top: 40px;
            display: table;
            width: 100%;
        }

        .signature-cell {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 20px;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 12px;
        }

        .paid-stamp {
            position: absolute;
            top: 100px;
            right: 50px;
            transform: rotate(-15deg);
            border: 3px solid #28a745;
            color: #28a745;
            font-size: 24px;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 10px;
            background: rgba(40, 167, 69, 0.1);
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="paid-stamp">PAID</div>
        
        <div class="header">
            <h1>{{ $organizationName }}</h1>
            <p>{{ $organizationAddress }}</p>
            <p>Phone: {{ $organizationPhone }} | Email: {{ $organizationEmail }}</p>
        </div>

        <div class="receipt-info">
            <h2>PAYMENT RECEIPT</h2>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-cell">Receipt Number:</div>
                    <div class="info-cell">{{ $receipt->receipt_number }}</div>
                </div>
                <div class="info-row">
                    <div class="info-cell">Receipt Date:</div>
                    <div class="info-cell">{{ $receipt->receipt_date->format('d-m-Y') }}</div>
                </div>
                <div class="info-row">
                    <div class="info-cell">Payment Month:</div>
                    <div class="info-cell">{{ $payment->payment_month }}</div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="payment-details">
                <h3>Resident Information</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-cell">Resident Name:</div>
                        <div class="info-cell">{{ $resident->owner_name ?? $resident->name }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-cell">House Number:</div>
                        <div class="info-cell">{{ $resident->house_number }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-cell">Floor:</div>
                        <div class="info-cell">{{ $resident->floor ?? 'N/A' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-cell">Contact:</div>
                        <div class="info-cell">{{ $resident->phone_number }}</div>
                    </div>
                </div>
            </div>

            <div class="payment-details">
                <h3>Payment Information</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-cell">Payment Date:</div>
                        <div class="info-cell">{{ $payment->payment_date ? $payment->payment_date->format('d-m-Y') : 'N/A' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-cell">Payment Method:</div>
                        <div class="info-cell">{{ $payment->payment_method ?? 'Cash' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-cell">Transaction ID:</div>
                        <div class="info-cell">{{ $payment->transaction_id ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <div class="amount-section">
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-cell">Amount Due:</div>
                        <div class="info-cell">₹ {{ number_format($payment->amount_due, 2) }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-cell">Amount Paid:</div>
                        <div class="info-cell">₹ {{ number_format($payment->amount_paid, 2) }}</div>
                    </div>
                    @if($receipt->tax_amount > 0)
                    <div class="info-row">
                        <div class="info-cell">Tax Amount:</div>
                        <div class="info-cell">₹ {{ number_format($receipt->tax_amount, 2) }}</div>
                    </div>
                    @endif
                </div>

                <div class="total-amount">
                    Total Amount Received: ₹ {{ number_format($receipt->total_amount, 2) }}
                </div>

                <div class="amount-words">
                    <strong>Amount in Words:</strong> 
                    @php
                        // Simple number to words conversion for Indian currency
                        function numberToWords($number) {
                            $ones = array(
                                0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
                                6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
                                11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
                                16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'
                            );
                            $tens = array(
                                2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
                                6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
                            );
                            
                            if ($number < 20) return $ones[$number];
                            if ($number < 100) return $tens[intval($number/10)] . ($number%10 ? ' ' . $ones[$number%10] : '');
                            if ($number < 1000) return $ones[intval($number/100)] . ' Hundred' . ($number%100 ? ' ' . numberToWords($number%100) : '');
                            if ($number < 100000) return numberToWords(intval($number/1000)) . ' Thousand' . ($number%1000 ? ' ' . numberToWords($number%1000) : '');
                            if ($number < 10000000) return numberToWords(intval($number/100000)) . ' Lakh' . ($number%100000 ? ' ' . numberToWords($number%100000) : '');
                            return numberToWords(intval($number/10000000)) . ' Crore' . ($number%10000000 ? ' ' . numberToWords($number%10000000) : '');
                        }
                        $amount_words = numberToWords(intval($receipt->total_amount));
                    @endphp
                    {{ $amount_words }} Rupees Only
                </div>
            </div>

            @if($receipt->notes)
            <div class="payment-details">
                <h3>Notes</h3>
                <p>{{ $receipt->notes }}</p>
            </div>
            @endif

            <div class="signatures">
                <div class="signature-cell">
                    <div class="signature-line">
                        Received By<br>
                        (Society Representative)
                    </div>
                </div>
                <div class="signature-cell">
                    <div class="signature-line">
                        Received From<br>
                        ({{ $resident->owner_name ?? $resident->name }})
                    </div>
                </div>
            </div>

            <div class="footer">
                <p>This is a computer-generated receipt and does not require a signature.</p>
                <p>For any queries, please contact {{ $organizationPhone }} or {{ $organizationEmail }}</p>
                <p>Generated on: {{ now()->format('d-m-Y H:i:s') }}</p>
            </div>
        </div>
    </div>
</body>
</html>