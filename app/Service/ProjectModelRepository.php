<?php

namespace App\Service;

use K2D\Core\Models\ConfigurationModel;
use K2D\Core\Models\LogModel;
use K2D\Core\Service\ModelRepository;
use K2D\File\Model\FileModel;
use K2D\News\Models\NewModel;
use Nette\Database\Table\Selection;

/**
 * @property-read NewModel $new
 * @property-read FileModel $file
 */
class ProjectModelRepository extends ModelRepository
{
    public function getPublicNews(string $lang): ?Selection
    {
        return $this->new->getTable()->where('public', 1)->where('lang', $lang)->order('created DESC')->order('id DESC');
    }
}
