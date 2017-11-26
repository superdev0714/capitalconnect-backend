<html>
<body>

<p>
    Dear Natalie,
</p>

<p>
    Following payment from {{ $customer['name'] }} &lt;{{ $customer['email'] }}&gt; was successfully received.
</p>

<p>
    Reference ID: {{ $payment['reference'] }}<br>
    Amount: R{{ $payment['amount'] }}
</p>

<p>
    Regards
</p>

</body>
</html>

