<?php

declare(strict_types=1);

namespace Pragmatic\StateMachine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Polymorphic state model for storing state machine data.
 *
 * @property int $id
 * @property string $stateful_type
 * @property int $stateful_id
 * @property string|null $current_state
 * @property array|null $data
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @mixin IdeHelperState
 */
class State extends Model
{
    protected $table = 'states';

    protected $guarded = ['id'];

    /**
     * The entity that owns this state.
     */
    public function stateful(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }
}
