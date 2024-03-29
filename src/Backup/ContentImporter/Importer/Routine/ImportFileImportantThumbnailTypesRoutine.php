<?php

namespace Concrete\Core\Backup\ContentImporter\Importer\Routine;

use Concrete\Core\Entity\File\Image\Thumbnail\Type\Type as ThumbnailType;
use Concrete\Core\Entity\File\Image\Thumbnail\Type\TypeFileSet;
use Concrete\Core\File\Set\Set as FileSet;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Utility\Service\Xml;
use Doctrine\ORM\EntityManagerInterface;
use SimpleXMLElement;

class ImportFileImportantThumbnailTypesRoutine extends AbstractRoutine
{
    public function getHandle()
    {
        return 'file_important_thumbnail_types';
    }

    public function import(SimpleXMLElement $sx)
    {
        if (isset($sx->thumbnailtypes)) {
            $app = Application::getFacadeApplication();
            $em = $app->make(EntityManagerInterface::class);
            $repo = $em->getRepository(ThumbnailType::class);
            $xml = $app->make(Xml::class);
            foreach ($sx->thumbnailtypes->thumbnailtype as $l) {
                $handle = (string) $l['handle'];
                if ($repo->findOneBy(['ftTypeHandle' => $handle]) !== null) {
                    continue;
                }
                $type = new ThumbnailType();
                $type->setName((string) $l['name']);
                $type->setHandle((string) $l['handle']);
                if (isset($l['sizingMode'])) {
                    $type->setSizingMode((string) $l['sizingMode']);
                }
                $type->setIsUpscalingEnabled($xml->getBool($l['upscalingEnabled']));
                $type->setKeepAnimations($xml->getBool($l['keepAnimations']));
                if (isset($l['width'])) {
                    $type->setWidth((string) $l['width']);
                }
                if (isset($l['height'])) {
                    $type->setHeight((string) $l['height']);
                }
                if ($xml->getBool($l['required'])) {
                    $type->requireType();
                }
                $type->setLimitedToFileSets($xml->getBool($l['limitedToFileSets']));
                if (isset($l->filesets)) {
                    foreach ($l->filesets->fileset as $xFileSet) {
                        $name = isset($xFileSet['name']) ? trim((string) $xFileSet['name']) : '';
                        if ($name !== '') {
                            $fileSet = FileSet::getByName($name);
                            if ($fileSet === null) {
                                $fileSet = FileSet::create($name);
                            }
                            if ($fileSet->getFileSetType() == FileSet::TYPE_PUBLIC) {
                                $type->getAssociatedFileSets()->add(new TypeFileSet($type, $fileSet));
                            }
                        }
                    }
                }
                $em->persist($type);
                $em->flush();
            }
        }
    }
}
