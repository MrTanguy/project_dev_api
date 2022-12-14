<?php

namespace App\Entity;

use App\Repository\CompanyRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Asset;

use Hateoas\Configuration\Annotation as Hateoas;
/**
 * @Hateoas\Relation(
 *    "self",
 *    href=@Hateoas\Route(
 *         "company.get",
 *         parameters={
 *             "idCompany" = "expr(object.getId())"
 *         }
 *    ),
 *    exclusion = @Hateoas\Exclusion(groups="getAllCompanies")
 * )
 */

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getCompany", "getAllCompanies"])]
    private ?int $id = null;

    #[Asset\NotBlank(message: "Une companie doit avoir un nom")]
    #[ORM\Column(length: 255)]
    #[Groups(["getCompany", "getAllCompanies"])]
    private ?string $name = null;

    #[Asset\NotBlank(message: "Un job doit avoir un nom")]
    #[ORM\Column(length: 255)]
    #[Groups(["getCompany", "getAllCompanies"])]
    private ?string $job = null;

    #[ORM\Column(length: 50)]
    #[Asset\Choice(
        choices: ['on', 'off'],
        message: 'Le status doit être on ou off'
    )]
    #[Groups(["getCompany", "getAllCompanies"])]
    private ?string $status = null;

    #[Asset\NotBlank(message: "La latitude est obligatoire")]
    #[Asset\Range(
        min: -90,
        max: 90,
        notInRangeMessage: "La latitude doit être comprise entre -90 et 90"
    )]
    #[ORM\Column]
    #[Groups(["getCompany", "getAllCompanies"])]
    private ?float $lat = null;

    #[Asset\NotBlank(message: "La longitude est obligatoire")]
    #[Asset\Range(
        min: -180,
        max: 180,
        notInRangeMessage: "La longitude doit être comprise entre -180 et 180"
    )]
    #[ORM\Column]
    #[Groups(["getCompany", "getAllCompanies"])]
    private ?float $lon = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getJob(): ?string
    {
        return $this->job;
    }

    public function setJob(string $job): self
    {
        $this->job = $job;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getLat(): ?float
    {
        return $this->lat;
    }

    public function setLat(float $lat): self
    {
        $this->lat = $lat;

        return $this;
    }

    public function getLon(): ?float
    {
        return $this->lon;
    }

    public function setLon(float $lon): self
    {
        $this->lon = $lon;

        return $this;
    }
}
