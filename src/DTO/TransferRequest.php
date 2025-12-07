<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class TransferRequest
{
    #[Assert\NotBlank(message: 'Source account number is required')]
    #[Assert\Length(min: 10, max: 20)]
    public string $sourceAccountNumber;

    #[Assert\NotBlank(message: 'Destination account number is required')]
    #[Assert\Length(min: 10, max: 20)]
    public string $destinationAccountNumber;

    #[Assert\NotBlank(message: 'Amount is required')]
    #[Assert\Positive(message: 'Amount must be positive')]
    #[Assert\Regex(
        pattern: '/^\d+(\.\d{1,2})?$/',
        message: 'Amount must be a valid decimal with up to 2 decimal places'
    )]
    public string $amount;

    #[Assert\Length(max: 500)]
    public ?string $description = null;
}
