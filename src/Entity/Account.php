<?php

namespace App\Entity;

use App\Repository\AccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[ORM\Table(name: 'accounts')]
#[ORM\Index(columns: ['account_number'], name: 'idx_account_number')]
#[ORM\Index(columns: ['status'], name: 'idx_status')]
class Account
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 20, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 20)]
    private string $accountNumber;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    private string $holderName;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private string $balance = '0.00';

    #[ORM\Column(type: Types::STRING, length: 3)]
    #[Assert\NotBlank]
    #[Assert\Currency]
    private string $currency = 'USD';

    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\Choice(choices: ['active', 'suspended', 'closed'])]
    private string $status = 'active';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'sourceAccount')]
    private Collection $outgoingTransactions;

    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'destinationAccount')]
    private Collection $incomingTransactions;

    #[ORM\Version]
    #[ORM\Column(type: Types::INTEGER)]
    private int $version = 0;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->outgoingTransactions = new ArrayCollection();
        $this->incomingTransactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccountNumber(): string
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(string $accountNumber): self
    {
        $this->accountNumber = $accountNumber;
        return $this;
    }

    public function getHolderName(): string
    {
        return $this->holderName;
    }

    public function setHolderName(string $holderName): self
    {
        $this->holderName = $holderName;
        return $this;
    }

    public function getBalance(): string
    {
        return $this->balance;
    }

    public function setBalance(string $balance): self
    {
        $this->balance = $balance;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getOutgoingTransactions(): Collection
    {
        return $this->outgoingTransactions;
    }

    public function getIncomingTransactions(): Collection
    {
        return $this->incomingTransactions;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function debit(string $amount): void
    {
        if (!$this->isActive()) {
            throw new \DomainException('Cannot debit inactive account');
        }

        $newBalance = bcsub($this->balance, $amount, 2);
        if (bccomp($newBalance, '0', 2) < 0) {
            throw new \DomainException('Insufficient funds');
        }

        $this->balance = $newBalance;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function credit(string $amount): void
    {
        if (!$this->isActive()) {
            throw new \DomainException('Cannot credit inactive account');
        }

        $this->balance = bcadd($this->balance, $amount, 2);
        $this->updatedAt = new \DateTimeImmutable();
    }
}
