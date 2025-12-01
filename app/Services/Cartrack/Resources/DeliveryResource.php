<?php

namespace App\Services\Cartrack\Resources;

use App\Services\Cartrack\CartrackResponse;
use InvalidArgumentException;

class DeliveryResource extends AbstractResource
{
    public function jobs(array $query = []): CartrackResponse
    {
        return $this->client->get('/delivery/jobs', $query);
    }

    public function job(int|string $jobId): CartrackResponse
    {
        return $this->client->get("/delivery/jobs/{$jobId}");
    }

    public function createJob(array $payload): CartrackResponse
    {
        return $this->client->post('/delivery/jobs', $this->validateJobPayload($payload));
    }

    public function updateJob(int|string $jobId, array $payload): CartrackResponse
    {
        return $this->client->put("/delivery/jobs/{$jobId}", $this->validateJobPayload($payload));
    }

    public function deleteJob(int|string $jobId, bool $force = false): CartrackResponse
    {
        $query = $force ? ['force' => true] : [];

        return $this->client->delete("/delivery/jobs/{$jobId}", $query);
    }

    public function bulkUploadJobs(array|string $payload, array $query = []): CartrackResponse
    {
        if (is_string($payload) && is_file($payload)) {
            return $this->client->upload('/delivery/jobs/bulk-upload', $payload, $query);
        }

        return $this->client->post('/delivery/jobs/bulk-upload', $payload, $query);
    }

    public function completeJobs(array $payload): CartrackResponse
    {
        return $this->client->post('/delivery/jobs/complete', $payload);
    }

    public function drivers(array $query = []): CartrackResponse
    {
        return $this->client->get('/delivery/drivers', $query);
    }

    public function equipment(array $query = []): CartrackResponse
    {
        return $this->client->get('/delivery/equipment', $query);
    }

    public function plans(array $query = []): CartrackResponse
    {
        return $this->client->get('/delivery/plans', $query);
    }

    public function deletePlan(int|string $planId): CartrackResponse
    {
        return $this->client->delete("/delivery/plans/{$planId}");
    }

    protected function validateJobPayload(array $payload): array
    {
        if (isset($payload['schedule_type_id']) && !in_array((int) $payload['schedule_type_id'], [2, 3], true)) {
            throw new InvalidArgumentException('schedule_type_id deve ser 2 (Scheduled) ou 3 (Unscheduled).');
        }

        return $payload;
    }
}
