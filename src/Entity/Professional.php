<?php

namespace App\Entity;

use App\Repository\ProfessionalRepository;
use Doctrine\ORM\Mapping as ORM;
#use Symfony\Component\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Asset;

use Hateoas\Configuration\Annotation as Hateoas;
/**
 * @Hateoas\Relation(
 *    "self",
 *    href=@Hateoas\Route(
 *         "professional.get",
 *         parameters={
 *             "idProfessional" = "expr(object.getId())"
 *         }
 *    ),
 *    exclusion = @Hateoas\Exclusion(groups="getAllProfessionals")
 * )
 */

#[ORM\Entity(repositoryClass: ProfessionalRepository::class)]
class Professional
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getProfessionals", "getAllProfessionals"])]
    private ?int $id = null;

    #[Asset\NotBlank(message: "Un professionel doit avoir un prenom")]
    #[ORM\Column(length: 255)]
    #[Groups(["getProfessionals", "getAllProfessionals"])]
    private ?string $firstname = null;

    #[Asset\NotBlank(message: "Un professionel doit avoir un nom")]
    #[ORM\Column(length: 255)]
    #[Groups(["getProfessionals", "getAllProfessionals"])]
    private ?string $lastname = null;

    #[Asset\NotBlank(message: "Un job doit avoir un nom")]
    #[ORM\Column(length: 255)]
    #[Groups(["getProfessionals", "getAllProfessionals"])]
    private ?string $job = null;

    #[ORM\Column(length: 255)]
    #[Asset\NotBlank(message: "Un professionel doit avoir un status")]
    #[Asset\NotNull()]
    #[Asset\Choice(
        choices: ['on', 'off'],
        message: 'Error status'
    )]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["getProfessionals", "getAllProfessionals"])]
    private ?int $company_job_id = null;

    #[ORM\Column]
    #[Groups(["getProfessionals", "getAllProfessionals"])]
    private ?int $noteCount = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["getProfessionals", "getAllProfessionals"])]
    private ?float $noteAvg = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

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

    public function getCompanyJobId(): ?int
    {
        return $this->company_job_id;
    }

    public function setCompanyJobId(?int $company_job_id): self
    {
        $this->company_job_id = $company_job_id;

        return $this;
    }

    public function getNoteCount(): ?int
    {
        return $this->noteCount;
    }

    public function setNoteCount(int $noteCount): self
    {
        $this->noteCount = $noteCount;

        return $this;
    }

    public function getNoteAvg(): ?float
    {
        return $this->noteAvg;
    }

    public function setNoteAvg(?float $noteAvg): self
    {
        $this->noteAvg = $noteAvg;

        return $this;
    }
}
