export type RseBadge = "MESURE" | "CALCULE" | "ESTIME";

// Types pour le rapport RSE
export interface RseScope {
    org_id: number | null;
    org_name: string | null;
    year: number;
    generated_at: string;
}

// Couverture des données et qualité des mesures
export interface RseCoverage {
    devices_total: number;
    devices_reporting: number;
    uptime_pct: number;
    data_points: number;
}
// Données de démonstration pour une plante
export interface RsePlantDemo {
    device_id: string | null;
    plant_type: string;
    plant_size: string;
    leaf_area_m2: number;
    method_note: string;
}
// KPI individuels pour le rapport RSE
export interface RseKpi {
    key: string;
    label: string;
    value: number;
    unit: string;
    badge: RseBadge;
    detail: string;
}
// Réponse complète du rapport RSE
export interface RseReportResponse {
    scope: RseScope;
    coverage: RseCoverage;
    plant_demo: RsePlantDemo;
    kpis: RseKpi[];
    breakdown: {
        optimal_conditions: {
            soil_ok_pct: number;
            temp_ok_pct: number;
            light_ok_pct: number;
            overall_ok_pct: number;
        };
        alerts: {
            total: number;
            critical: number;
            warning: number;
            avg_resolution_hours: number;
        };
        missions: {
            total: number;
            maintenance: number;
            replacement: number;
            installation: number;
        };
    };
    charts: {
        health_score_monthly: Array<{ month: string; value: number }>;
        alerts_monthly: Array<{
            month: string;
            critical: number;
            warning: number;
        }>;
        time_in_optimal_monthly: Array<{ month: string; value: number }>;
    };
    by_species: Array<{
        slug: string;
        name: string;
        plants: number;
        time_in_optimal_pct: number;
        health_score_avg: number;
        co2_est_kg_year: number;
        alerts_total: number;
    }>;
    top_plants: Array<{
        name: string;
        device_id: string;
        species: string;
        location: {
            building?: string;
            floor?: string;
            zone?: string;
            [key: string]: unknown;
        } | null;
        co2_est_kg_year: number;
        time_in_optimal_pct: number;
        health_score_avg: number;
    }>;
    disclaimer: string[];
}
