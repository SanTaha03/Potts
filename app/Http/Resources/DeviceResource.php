<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Calcul du statut "live" (pour la démo : 2 minutes)
        // En prod, on mettrait plutôt 24h ou dynamique selon le type de device
        $isOffline = $this->last_seen_at ? $this->last_seen_at->diffInMinutes(now()) > 2 : true;
        
        $displayStatus = $this->status;
        if ($this->status === 'active' && $isOffline) {
            $displayStatus = 'offline';
        }

        // Normalisation de last_values pour éviter les null côté front
        $safeLastValues = [
            'soil_pct'  => $this->last_values['soil_pct'] ?? null,
            'temp_c'    => $this->last_values['temp_c'] ?? null,
            'light_pct' => $this->last_values['light_pct'] ?? null,
            'sent_at'   => $this->last_values['sent_at'] ?? null,
            'battery'   => $this->last_values['battery'] ?? ($this->meta['battery_level'] ?? null),
        ];

        return [
            'id' => $this->id,
            'device_id' => $this->device_id, // Identifiant stable
            'name' => $this->name,           // Nom affichable
            
            'status' => $displayStatus,       // 'active', 'offline', 'inactive', 'archived'
            'is_online' => !$isOffline, 
            
            'location' => $this->location,
            'meta' => $this->meta,
            
            'last_seen_at' => $this->last_seen_at?->toISOString(),
            'last_values' => $safeLastValues, // Objet stable garanti
            
            // Legacy : champs conservés temporairement si d'autres parties du code en dépendent, 
            // mais idéalement à supprimer.
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
