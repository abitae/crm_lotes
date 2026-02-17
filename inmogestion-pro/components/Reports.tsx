
import React, { useMemo, useState } from 'react';
import { Lot, Advisor, LotStatus, AdvisorLevel } from '../types';
import { 
  BarChart, 
  Bar, 
  XAxis, 
  YAxis, 
  CartesianGrid, 
  Tooltip, 
  ResponsiveContainer, 
  Cell
} from 'recharts';
import { Target, TrendingUp, Flag, Trophy, AlertTriangle, ChevronRight } from 'lucide-react';

interface ReportsProps {
  lots: Lot[];
  advisors: Advisor[];
}

const Reports: React.FC<ReportsProps> = ({ lots, advisors }) => {
  // Extraer niveles únicos dinámicamente de los asesores existentes
  const availableLevels = useMemo(() => {
    // Explicitly cast to string[] to resolve type inference issues where 'a' and 'b' in sort become 'unknown'
    const levels = [...new Set(advisors.map(a => a.level))] as string[];
    // Ordenar niveles numéricamente (Nivel 1, Nivel 2, etc.)
    return levels.sort((a, b) => {
      const numA = parseInt(a.replace(/\D/g, '')) || 0;
      const numB = parseInt(b.replace(/\D/g, '')) || 0;
      return numA - numB;
    });
  }, [advisors]);

  const [selectedLevel, setSelectedLevel] = useState<AdvisorLevel>(availableLevels[0] || 'NIVEL 1');

  // Global Context
  const globalStats = useMemo(() => {
    const sold = lots.filter(l => l.status !== LotStatus.AVAILABLE).reduce((acc, l) => acc + l.price, 0);
    const goal = advisors.reduce((acc, a) => acc + a.personalQuota, 0);
    return { sold, goal, pct: Math.round((sold / (goal || 1)) * 100) };
  }, [lots, advisors]);

  // Level Specific Data
  const levelData = useMemo(() => {
    const levelAdvisors = advisors.filter(a => a.level === selectedLevel);
    const levelLots = lots.filter(l => l.advisorId && levelAdvisors.some(a => a.id === l.advisorId));
    const sold = levelLots.filter(l => l.status !== LotStatus.AVAILABLE).reduce((acc, l) => acc + l.price, 0);
    const goal = levelAdvisors.reduce((acc, a) => acc + a.personalQuota, 0);
    
    const sellersPerformance = levelAdvisors.map(adv => {
      const advLots = lots.filter(l => l.advisorId === adv.id && l.status !== LotStatus.AVAILABLE);
      const achieved = advLots.reduce((acc, l) => acc + l.price, 0);
      return {
        name: adv.name.split(' ').slice(-1)[0],
        full: adv.name,
        Logrado: achieved,
        Meta: adv.personalQuota,
        pct: Math.round((achieved / (adv.personalQuota || 1)) * 100)
      };
    }).sort((a,b) => b.Logrado - a.Logrado);

    return { 
      count: levelAdvisors.length,
      sold, 
      goal, 
      pct: Math.round((sold / (goal || 1)) * 100),
      sellers: sellersPerformance
    };
  }, [lots, advisors, selectedLevel]);

  // Colores dinámicos basados en el número de nivel
  const getLevelTheme = (level: string) => {
    const num = parseInt(level.replace(/\D/g, '')) || 1;
    const colors = [
      { color: '#3b82f6', bg: 'bg-blue-50', text: 'text-blue-700' },
      { color: '#10b981', bg: 'bg-emerald-50', text: 'text-emerald-700' },
      { color: '#6366f1', bg: 'bg-indigo-50', text: 'text-indigo-700' },
      { color: '#a855f7', bg: 'bg-purple-50', text: 'text-purple-700' },
      { color: '#ec4899', bg: 'bg-pink-50', text: 'text-pink-700' },
      { color: '#f59e0b', bg: 'bg-amber-50', text: 'text-amber-700' }
    ];
    return colors[(num - 1) % colors.length];
  };

  const currentTheme = getLevelTheme(selectedLevel);

  return (
    <div className="space-y-8 pb-12">
      <div className="flex flex-col xl:flex-row gap-6">
        <div className="flex-1 bg-slate-900 text-white p-8 rounded-3xl shadow-2xl relative overflow-hidden">
          <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
              <h2 className="text-3xl font-black tracking-tighter uppercase mb-2">Monitor General</h2>
              <p className="text-slate-400 font-bold uppercase text-xs tracking-widest flex items-center gap-2">
                <Flag className="w-4 h-4 text-emerald-500" />
                Meta Corporativa Total
              </p>
            </div>
            <div className="text-right">
              <p className="text-4xl font-black text-emerald-400 leading-none">S/ {globalStats.sold.toLocaleString()}</p>
              <p className="text-[10px] text-slate-500 font-bold uppercase mt-1">Meta: S/ {globalStats.goal.toLocaleString()}</p>
            </div>
          </div>
          <div className="mt-8">
            <div className="flex justify-between items-end mb-2">
              <span className="text-[10px] font-black uppercase text-slate-500">Progreso Total</span>
              <span className="text-xl font-black text-white">{globalStats.pct}%</span>
            </div>
            <div className="w-full bg-slate-800 h-3 rounded-full overflow-hidden">
              <div 
                className="h-full bg-gradient-to-r from-emerald-600 to-emerald-400 rounded-full transition-all duration-1000"
                style={{ width: `${globalStats.pct}%` }}
              ></div>
            </div>
          </div>
        </div>

        {/* Dynamic Level Navigation */}
        <div className="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-center min-w-[280px]">
          <p className="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 px-2">Explorar Niveles:</p>
          <div className="flex flex-col gap-2 max-h-[300px] overflow-y-auto custom-scrollbar pr-2">
            {availableLevels.map(level => {
              const theme = getLevelTheme(level);
              return (
                <button
                  key={level}
                  onClick={() => setSelectedLevel(level)}
                  className={`flex items-center justify-between p-3 rounded-2xl transition-all ${
                    selectedLevel === level 
                    ? `${theme.bg} ${theme.text} ring-2 ring-current` 
                    : 'bg-slate-50 text-slate-400 hover:bg-slate-100'
                  }`}
                >
                  <div className="flex items-center gap-3">
                    <div className={`w-7 h-7 rounded-xl flex items-center justify-center font-black text-[10px] ${
                      selectedLevel === level ? 'bg-white shadow-sm' : 'bg-slate-200'
                    }`}>
                      {level.replace(/\D/g, '') || '?'}
                    </div>
                    <span className="font-black text-[11px] uppercase">{level}</span>
                  </div>
                  <ChevronRight className={`w-3 h-3 ${selectedLevel === level ? 'opacity-100' : 'opacity-0'}`} />
                </button>
              );
            })}
          </div>
        </div>
      </div>

      <div className="space-y-6">
        <div className="flex items-center gap-3">
          <div className="w-1.5 h-8 rounded-full" style={{backgroundColor: currentTheme.color}}></div>
          <h3 className="text-xl font-black text-slate-800 uppercase tracking-tight">Reporte Detallado: {selectedLevel}</h3>
          <span className="text-xs font-bold text-slate-400">({levelData.count} Vendedores)</span>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div className="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div className="relative z-10">
              <div className="flex items-center justify-between mb-8">
                <div className={`p-3 rounded-2xl ${currentTheme.bg}`}>
                  <Target className={`w-6 h-6 ${currentTheme.text}`} />
                </div>
                <span className={`text-2xl font-black ${currentTheme.text}`}>{levelData.pct}%</span>
              </div>
              <p className="text-sm font-bold text-slate-400 uppercase mb-1">Logro Acumulado</p>
              <h4 className="text-3xl font-black text-slate-800 leading-none mb-2">S/ {levelData.sold.toLocaleString()}</h4>
              <p className="text-[10px] font-bold text-slate-400 uppercase">Meta del Nivel: S/ {levelData.goal.toLocaleString()}</p>
            </div>
            <div className="absolute -right-20 -top-20 w-48 h-48 rounded-full blur-3xl opacity-10" style={{backgroundColor: currentTheme.color}}></div>
          </div>

          <div className="lg:col-span-2 bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
            <h4 className="text-sm font-black text-slate-800 uppercase flex items-center gap-2 mb-8">
              <TrendingUp className="w-4 h-4 text-emerald-500" />
              Ranking de Productividad
            </h4>
            <div className="h-[250px]">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={levelData.sellers.slice(0, 15)}>
                  <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                  <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{fontSize: 9, fontWeight: 800, fill: '#64748b'}} />
                  <YAxis hide />
                  <Tooltip 
                    cursor={{fill: '#f8fafc'}}
                    contentStyle={{borderRadius: '16px', border: 'none', boxShadow: '0 20px 25px -5px rgb(0 0 0 / 0.1)', fontSize: '11px', fontWeight: 'bold'}}
                  />
                  <Bar dataKey="Logrado" radius={[6, 6, 0, 0]} barSize={25}>
                    {levelData.sellers.slice(0, 15).map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={entry.pct >= 100 ? '#10b981' : entry.pct >= 50 ? currentTheme.color : '#f43f5e'} />
                    ))}
                  </Bar>
                </BarChart>
              </ResponsiveContainer>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <h4 className="text-[10px] font-black text-slate-500 uppercase tracking-widest">Listado de Rendimiento Completo - {selectedLevel}</h4>
            <div className="flex items-center gap-4">
              <div className="flex items-center gap-1.5">
                <span className="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span className="text-[9px] font-bold text-slate-400 uppercase">Excelente</span>
              </div>
              <div className="flex items-center gap-1.5">
                <span className="w-2 h-2 rounded-full bg-red-500"></span>
                <span className="text-[9px] font-bold text-slate-400 uppercase">Crítico</span>
              </div>
            </div>
          </div>
          <div className="max-h-[400px] overflow-y-auto">
            <table className="w-full text-left">
              <thead className="sticky top-0 bg-white shadow-sm z-10 border-b border-slate-100">
                <tr>
                  <th className="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Asesor</th>
                  <th className="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Venta</th>
                  <th className="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Meta</th>
                  <th className="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Estado</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-50">
                {levelData.sellers.map((seller, idx) => (
                  <tr key={seller.full} className="hover:bg-slate-50 transition-colors">
                    <td className="px-8 py-4">
                      <p className="text-sm font-black text-slate-800 uppercase">{seller.full}</p>
                      <p className="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">Posición #{idx + 1}</p>
                    </td>
                    <td className="px-8 py-4">
                      <p className="text-sm font-black text-slate-700">S/ {seller.Logrado.toLocaleString()}</p>
                    </td>
                    <td className="px-8 py-4">
                      <div className="flex items-center gap-3">
                        <div className="flex-1 bg-slate-100 h-1.5 rounded-full overflow-hidden max-w-[100px]">
                          <div 
                            className={`h-full rounded-full transition-all duration-1000 ${
                              seller.pct >= 100 ? 'bg-emerald-500' : seller.pct >= 50 ? currentTheme.color : 'bg-red-500'
                            }`}
                            style={{ width: `${Math.min(seller.pct, 100)}%` }}
                          ></div>
                        </div>
                        <span className="text-xs font-black text-slate-600">{seller.pct}%</span>
                      </div>
                    </td>
                    <td className="px-8 py-4 text-right">
                      {seller.pct >= 100 ? (
                        <Trophy className="w-5 h-5 text-amber-400 inline" />
                      ) : seller.pct < 30 ? (
                        <AlertTriangle className="w-5 h-5 text-red-400 inline" />
                      ) : (
                        <TrendingUp className="w-5 h-5 text-slate-300 inline" />
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Reports;
