<?php
/**
 * Mock M-Pesa API Integration
 * 
 * In a real application, this would integrate with the actual M-Pesa API.
 * For this example, we'll simulate the API response.
 */

function processMpesaPayment($phoneNumber, $amount) {
    // Validate phone number (simple validation for demo)
    if (empty($phoneNumber) || strlen($phoneNumber) < 10) {
        return array(
            'success' => false,
            'message' => 'Invalid phone number'
        );
    }
    
    // Validate amount
    if ($amount <= 0) {
        return array(
            'success' => false,
            'message' => 'Invalid amount'
        );
    }
    
    // In a real application, this would make an API call to M-Pesa
    // For this example, we'll simulate a successful payment 90% of the time
    $randomSuccess = (rand(1, 10) <= 9);
    
    if ($randomSuccess) {
        return array(
            'success' => true,
            'message' => 'Payment successful',
            'transaction_id' => 'MPESA' . rand(100000, 999999),
            'amount' => $amount,
            'phone' => $phoneNumber
        );
    } else {
        return array(
            'success' => false,
            'message' => 'Payment failed. Please try again.'
        );
    }
}
?>
