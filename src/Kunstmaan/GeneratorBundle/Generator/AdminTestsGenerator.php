<?php

namespace Kunstmaan\GeneratorBundle\Generator;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Container;

/**
 * Generates tests to test the admin backend generated by the default-site generator
 */
class AdminTestsGenerator extends  Generator
{

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $skeletonDir;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var DialogHelper
     */
    private $dialog;

    /**
     * @param Filesystem      $filesystem  The filesytem
     * @param string          $skeletonDir The skeleton directory
     * @param OutputInterface $output      The output
     * @param DialogHelper    $dialog      The dialog
     */
    public function __construct(Filesystem $filesystem, $skeletonDir, OutputInterface $output, DialogHelper $dialog)
    {
        $this->filesystem = $filesystem;
        $this->skeletonDir = $skeletonDir;
        $this->output = $output;
        $this->dialog = $dialog;
    }

    /**
     * @param Bundle    $bundle  The bundle
     * @param string    $rootDir The root directory
     */
    public function generate(Bundle $bundle, $rootDir)
    {
        $parameters = array(
            'namespace'         => $bundle->getNamespace(),
            'bundle'            => $bundle
        );

        $this->generateBehatTests($bundle);
        $this->generateUnitTests($bundle, $parameters);
    }

    /**
     * @param Bundle $bundle
     * @param array  $parameters The template parameters
     */
    public function generateUnitTests(Bundle $bundle, array $parameters)
    {
        $dirPath = $bundle->getPath();
        $fullSkeletonDir = $this->skeletonDir . '/Tests';

        $this->output->writeln('Generating Unit Tests : <info>OK</info>');
    }

    /**
     * @param Bundle $bundle
     */
    public function generateBehatTests(Bundle $bundle)
    {
        $dirPath = $bundle->getPath();
        $fullSkeletonDir = $this->skeletonDir . '/Features';

        $this->filesystem->copy($fullSkeletonDir . '/AdminLogin.feature', $dirPath . '/Features/AdminLogin.feature', true);

        $this->output->writeln('Generating Behat Tests : <info>OK</info>');
    }
}
