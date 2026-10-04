<?php

namespace App\Services\SuperAdmin;

use App\Models\Announcement;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/** Announcement changes made from the Super Admin panel. */
class AnnouncementService
{
    /**
     * @param  array{title:string, body:string, audience:string, starts_at:?\DateTimeInterface, ends_at:?\DateTimeInterface, is_active:bool}  $data
     *
     * @throws Throwable
     */
    public function create(SuperAdmin $actor, array $data): Announcement
    {
        return DB::transaction(function () use ($actor, $data) {
            $announcement = Announcement::create($data + ['created_by' => $actor->id]);

            activity('announcement')
                ->causedBy($actor)
                ->performedOn($announcement)
                ->withProperties(['title' => $announcement->title, 'audience' => $data['audience']])
                ->log('Announcement created');

            return $announcement;
        });
    }

    /**
     * @param  array{title:string, body:string, audience:string, starts_at:?\DateTimeInterface, ends_at:?\DateTimeInterface, is_active:bool}  $data
     *
     * @throws RuntimeException when the announcement no longer exists
     * @throws Throwable
     */
    public function update(SuperAdmin $actor, int $id, array $data): Announcement
    {
        return DB::transaction(function () use ($actor, $id, $data) {
            $announcement = $this->locked($id);
            $announcement->fill($data);
            $changed = array_keys($announcement->getDirty());

            if ($changed === []) {
                return $announcement;
            }

            $announcement->save();

            activity('announcement')
                ->causedBy($actor)
                ->performedOn($announcement)
                ->withProperties(['title' => $announcement->title, 'changed' => $changed])
                ->log('Announcement updated');

            return $announcement;
        });
    }

    /**
     * @throws RuntimeException when the announcement no longer exists
     * @throws Throwable
     */
    public function setActive(SuperAdmin $actor, int $id, bool $active): Announcement
    {
        return DB::transaction(function () use ($actor, $id, $active) {
            $announcement = $this->locked($id);

            if ($announcement->is_active === $active) {
                return $announcement;
            }

            $announcement->update(['is_active' => $active]);

            activity('announcement')
                ->causedBy($actor)
                ->performedOn($announcement)
                ->withProperties(['title' => $announcement->title, 'is_active' => $active])
                ->log($active ? 'Announcement switched on' : 'Announcement switched off');

            return $announcement;
        });
    }

    /**
     * @throws RuntimeException when the announcement no longer exists
     * @throws Throwable
     */
    public function delete(SuperAdmin $actor, int $id): Announcement
    {
        return DB::transaction(function () use ($actor, $id) {
            $announcement = $this->locked($id);
            $announcement->delete();

            activity('announcement')
                ->causedBy($actor)
                ->performedOn($announcement)
                ->withProperties(['title' => $announcement->title])
                ->log('Announcement deleted');

            return $announcement;
        });
    }

    private function locked(int $id): Announcement
    {
        $announcement = Announcement::query()->lockForUpdate()->find($id);

        if (! $announcement) {
            throw new RuntimeException('That announcement no longer exists.');
        }

        return $announcement;
    }
}
