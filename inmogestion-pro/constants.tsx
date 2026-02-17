
import { Project, Client, Advisor, Lot, LotStatus, AdvisorLevel } from './types';

export const PROJECTS: Project[] = [
  { id: 'p1', name: 'Villa Norte - Mito', location: 'Mito', totalLots: 45, totalBlocks: ['A', 'B', 'C'] },
  { id: 'p2', name: 'San Antonio 3', location: 'Orcotuna', totalLots: 80, totalBlocks: ['A', 'B', 'C', 'D', 'E', 'F'] },
  { id: 'p3', name: 'Mirador 3.1', location: 'Huancayo', totalLots: 50, totalBlocks: ['A', 'B'] },
  { id: 'p4', name: 'Residencial Los Olivos', location: 'Concepción', totalLots: 120, totalBlocks: ['A', 'B', 'C', 'D', 'E', 'F', 'G'] },
];

const generateAdvisors = (): Advisor[] => {
  const advisors: Advisor[] = [];
  
  // 5 Nivel 4 (Directores)
  for (let i = 1; i <= 5; i++) {
    advisors.push({
      id: `adv-n4-${i}`,
      name: `DIRECTOR EJECUTIVO ${i}`,
      phone: `90040000${i}`,
      email: `director${i}@inmopro.com`,
      level: 'NIVEL 4',
      personalQuota: 1000000
    });
  }

  // 30 Nivel 3 (Gerentes) - Reportan a Nivel 4
  for (let i = 1; i <= 30; i++) {
    const superior = advisors.filter(a => a.level === 'NIVEL 4')[i % 5];
    advisors.push({
      id: `adv-n3-${i}`,
      name: `GERENTE COMERCIAL ${i}`,
      phone: `9003000${i.toString().padStart(2, '0')}`,
      email: `gerente${i}@inmopro.com`,
      level: 'NIVEL 3',
      superiorId: superior.id,
      personalQuota: 500000
    });
  }

  // 50 Nivel 2 (Seniors) - Reportan a Nivel 3
  for (let i = 1; i <= 50; i++) {
    const superior = advisors.filter(a => a.level === 'NIVEL 3')[i % 30];
    advisors.push({
      id: `adv-n2-${i}`,
      name: `ASESOR SENIOR ${i}`,
      phone: `9002000${i.toString().padStart(2, '0')}`,
      email: `senior${i}@inmopro.com`,
      level: 'NIVEL 2',
      superiorId: superior.id,
      personalQuota: 250000
    });
  }

  // 60 Nivel 1 (Juniors) - Reportan a Nivel 2
  for (let i = 1; i <= 60; i++) {
    const superior = advisors.filter(a => a.level === 'NIVEL 2')[i % 50];
    advisors.push({
      id: `adv-n1-${i}`,
      name: `ASESOR NIVEL 1 - ${i}`,
      phone: `9001000${i.toString().padStart(2, '0')}`,
      email: `asesor${i}@inmopro.com`,
      level: 'NIVEL 1',
      superiorId: superior.id,
      personalQuota: 120000
    });
  }

  return advisors;
};

export const ADVISORS = generateAdvisors();

export const MOCK_CLIENTS: Client[] = Array.from({ length: 100 }).map((_, i) => ({
  id: `c${i}`,
  name: `CLIENTE DE EJEMPLO ${i + 1}`,
  dni: (20000000 + i).toString(),
  phone: `9800000${i.toString().padStart(2, '0')}`,
  email: `cliente${i}@correo.com`
}));

const generateLots = (): Lot[] => {
  const lots: Lot[] = [];
  const now = new Date();
  
  PROJECTS.forEach(p => {
    p.totalBlocks.forEach(block => {
      const count = p.id === 'p4' ? 20 : 15;
      for (let i = 1; i <= count; i++) {
        const statusRand = Math.random();
        const status = statusRand > 0.6 ? (statusRand > 0.8 ? LotStatus.TRANSFERRED : LotStatus.RESERVED) : LotStatus.AVAILABLE;
        const price = 25000 + (Math.random() * 15000);
        const advance = status !== LotStatus.AVAILABLE ? Math.round(price * (0.1 + Math.random() * 0.4)) : 0;
        
        // Random date within last 3 months
        const randomDaysAgo = Math.floor(Math.random() * 90);
        const contractDate = status !== LotStatus.AVAILABLE 
          ? new Date(new Date().setDate(now.getDate() - randomDaysAgo)).toISOString().split('T')[0]
          : undefined;

        const pool = status !== LotStatus.AVAILABLE 
          ? (Math.random() > 0.3 ? ADVISORS.filter(a => a.level === 'NIVEL 1' || a.level === 'NIVEL 2') : ADVISORS)
          : [];
          
        lots.push({
          id: `${p.id}-${block}-${i}`,
          projectId: p.id,
          block: block,
          number: i,
          area: 105.00,
          price: Math.round(price),
          status: status,
          advance: advance,
          remainingBalance: Math.round(price - advance),
          advisorId: status !== LotStatus.AVAILABLE ? pool[Math.floor(Math.random() * pool.length)].id : undefined,
          clientId: status !== LotStatus.AVAILABLE ? MOCK_CLIENTS[Math.floor(Math.random() * MOCK_CLIENTS.length)].id : undefined,
          contractDate: contractDate
        });
      }
    });
  });
  return lots;
};

export const INITIAL_LOTS = generateLots();
