<?php declare(strict_types = 1);

namespace App\Model;

use K2D\Core\Models\BaseModel;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class ParticipantModel extends BaseModel
{

    protected string $table = 'participant';

    public function getParticipant(int $id): ?ActiveRow
    {
        return $this->getTable()->where('id', $id)->fetch();
    }

    public function getParticipantsByCamp(int $camp_id): Selection
    {
        return $this->getTable()->where('camp_id', $camp_id);
    }
}
