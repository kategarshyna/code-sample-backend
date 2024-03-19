<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="users")
 * @UniqueEntity(fields={"email"}, groups={"new"}, message="This email is already in use")
 */
class User implements UserInterface
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=25)
     * @Assert\NotBlank(groups={"new"})
     * @Assert\Length(min=2, max=25, groups={"new"})
     */
    private ?string $firstName;

    /**
     * @ORM\Column(type="string", length=25)
     * @Assert\NotBlank(groups={"new"})
     * @Assert\Length(min=2, max=25, groups={"new"})
     */
    private ?string $lastName;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank(groups={"new", "login"})
     * @Assert\Email(groups={"new", "login"})
     */
    private ?string $email;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"new", "login"})
     * @Assert\Length(min=6, max=50, groups={"new"})
     * @Assert\Regex("/\d/", message="Your password must contain at least one digit", groups={"new"})
     */
    private ?string $password;

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private bool $active = true;

    /**
     * @ORM\OneToMany(targetEntity=Photo::class, mappedBy="user", orphanRemoval=true, cascade={"persist"})
     */
    private Collection $photos;

    /**
     * @ORM\OneToOne(targetEntity=Photo::class, cascade={"persist", "remove"})
     */
    private ?Photo $avatar;

    /**
     * @var ?string
     * @ORM\Column(name="salt", type="string", length=16);
     */
    protected ?string $salt;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
    }

    /**
     * @Groups({"user"})
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @Groups({"user"})
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @Groups({"user"})
     * @return Collection<int, Photo>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(Photo $photo): self
    {
        if (!$this->photos->contains($photo)) {
            $this->photos[] = $photo;
            $photo->setUser($this);
        }

        return $this;
    }

    public function removePhoto(Photo $photo): self
    {
        if ($this->photos->removeElement($photo)) {
            // set the owning side to null (unless already changed)
            if ($photo->getUser() === $this) {
                $photo->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @Groups({"user"})
     * @return Photo|null
     */
    public function getAvatar(): ?Photo
    {
        return $this->avatar;
    }

    public function setAvatar(?Photo $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @Groups({"user"})
     * @return string
     */
    public function getFullName(): string
    {
        return $this->firstName . " " . $this->lastName;
    }

    public function getUsername(): ?string
    {
        return $this->email;
    }

    public function getSalt(): ?string
    {
        return $this->salt;
    }

    /**
     * Sets salt to the user
     *
     * @param string|null $salt
     * @return $this
     */
    public function setSalt(?string $salt): self
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Method required by UserInterface
     * Returns an empty array, roles functionality is not implemented
     */
    public function getRoles(): array
    {
        return [];
    }

    /**
     * Method required by UserInterface
     * Does nothing, we don't store sensitive information on this object
     */
    public function eraseCredentials()
    {
    }
}
