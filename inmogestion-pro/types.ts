
export enum LotStatus {
  AVAILABLE = 'LIBRE',
  RESERVED = 'RESERVADO',
  TRANSFERRED = 'TRANSFERIDO',
  QUOTAS = 'CUOTAS'
}

export type AdvisorLevel = string;

export interface Lot {
  id: string;
  projectId: string;
  block: string;
  number: number;
  area: number;
  price: number;
  status: LotStatus;
  clientId?: string;
  advisorId?: string;
  advance?: number;
  remainingBalance?: number;
  paymentLimitDate?: string;
  operationNumber?: string;
  contractDate?: string;
  contractNumber?: string;
  notarialTransferDate?: string;
  observations?: string;
}

export interface Client {
  id: string;
  name: string;
  dni: string;
  phone: string;
  email?: string;
  referredBy?: string;
}

export interface Advisor {
  id: string;
  name: string;
  phone: string;
  email: string;
  level: AdvisorLevel;
  superiorId?: string; // ID of the person they report to
  personalQuota: number; // Goal for this specific seller
}

export interface Commission {
  id: string;
  lotId: string;
  advisorId: string;
  amount: number;
  percentage: number;
  type: 'DIRECTA' | 'PIRAMIDAL';
  status: 'PENDIENTE' | 'PAGADO';
  date: string;
}

export interface Project {
  id: string;
  name: string;
  location: string;
  totalLots: number;
  totalBlocks: string[];
}

export type ViewType = 'dashboard' | 'inventory' | 'finance' | 'clients' | 'reports' | 'sellers' | 'commissions';
