import { ensureCsrfCookie, http } from "./http";
import type { RseReportResponse } from "@/types/rse";

interface RseReportParams {
    year?: number;
    org_id?: number;
    device_id?: string;
}

export async function getRseReport(
    params: RseReportParams = {},
): Promise<RseReportResponse> {
    await ensureCsrfCookie();
    const { data } = await http.get<RseReportResponse>("/app/v1/rse/report", {
        params,
    });
    return data;
}
