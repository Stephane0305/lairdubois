<?php

namespace Ladb\CoreBundle\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\AuthoredTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_join",
 *		uniqueConstraints={
 *			@ORM\UniqueConstraint(name="ENTITY_USER_UNIQUE", columns={"entity_type", "entity_id", "user_id"})
 * 		},
 *		indexes={
 *			@ORM\Index(name="IDX_JOIN_ENTITY", columns={"entity_type", "entity_id"})
 *		})
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\JoinRepository")
 */
class Join implements AuthoredInterface {

	use AuthoredTrait;

	const CLASS_NAME = 'LadbCoreBundle:Core\Join';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(name="entity_type", type="smallint", nullable=false)
	 */
	private $entityType;

	/**
	 * @ORM\Column(name="entity_id", type="integer", nullable=false)
	 */
	private $entityId;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;

	// Id /////

	public function getId() {
		return $this->id;
	}

	// EntityType /////

	public function getEntityType() {
		return $this->entityType;
	}

	public function setEntityType($entityType) {
		$this->entityType = $entityType;
	}

	// EntityId /////

	public function getEntityId() {
		return $this->entityId;
	}

	public function setEntityId($entityId) {
		$this->entityId = $entityId;
		return $this;
	}

}