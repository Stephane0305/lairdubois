<?php

namespace Ladb\CoreBundle\Command;

use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Utils\KnowledgeUtils;
use Ladb\CoreBundle\Utils\TypableUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Ladb\CoreBundle\Utils\PropertyUtils;

class GenerateCompletionCountersCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:generate:completioncounters')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Generate completion counters')
			->setHelp(<<<EOT
The <info>ladb:generate:completioncounters</info> command generate completion counters
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');

		$om = $this->getContainer()->get('doctrine')->getManager();
		$knowledgeUtils = $this->getContainer()->get(KnowledgeUtils::NAME);

		$defs = array(
			array(
				'className' => \Ladb\CoreBundle\Entity\Knowledge\Book::CLASS_NAME,
			),
			array(
				'className' => \Ladb\CoreBundle\Entity\Knowledge\Provider::CLASS_NAME,
			),
			array(
				'className' => \Ladb\CoreBundle\Entity\Knowledge\School::CLASS_NAME,
			),
			array(
				'className' => \Ladb\CoreBundle\Entity\Knowledge\Software::CLASS_NAME,
			),
			array(
				'className' => \Ladb\CoreBundle\Entity\Knowledge\Wood::CLASS_NAME,
			),
		);

		foreach ($defs as $def) {

			$entityRepository = $om->getRepository($def['className']);
			$entities = $entityRepository->findAll();

			foreach ($entities as $entity) {

				if ($verbose) {
					$output->write(' <info>'.$entity->getTitle().'...</info>');
				}

				$knowledgeUtils->computeCompletionPercent($entity);

				if ($verbose) {
					$output->writeln(' <comment> ['.$entity->getCompletion100().'%]</comment>');
				}

			}

		}

		if ($forced) {
			$om->flush();
		}

	}

	/////

	private function _computeEntitiesCountersByUser($entityClassName, $entityName, $hiddable, $user, $forced, $verbose, OutputInterface $output) {

		$om = $this->getContainer()->get('doctrine')->getManager();

		// Retrieve Entities

		if ($verbose) {
			$output->write('<info> -- Retrieve '.$entityName.'... </info>');
		}

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'e' ))
			->from($entityClassName, 'e')
			->where('e.user = :user')
			->setParameter('user', $user)
		;

		try {
			$entities = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$entities = array();
		}

		$counters = array(
			'private' => 0,
			'public' => 0,
		);
		foreach ($entities as $entity) {
			if ($hiddable && $entity->getIsPrivate()) {
				$counters['private']++;
			} else {
				$counters['public']++;
			}
		}

		return $counters;
	}

}
