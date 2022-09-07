<?php declare(strict_types = 1);

namespace App\Model;

use K2D\Core\Models\BaseModel;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class CampModel extends BaseModel
{

    protected string $table = 'camp';

    public function getCamp(int $id): ?ActiveRow
    {
        return $this->getTable()->where('public', 1)->where('id', $id)->fetch();
    }

    public function getPublicCamps(): Selection
    {
        return $this->getTable()->where('public', 1)->order('date_from', 'ASC');
    }
}
