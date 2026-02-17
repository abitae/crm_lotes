
import React from 'react';
import { Lot, Project, LotStatus } from '../types';
import { 
  TrendingUp, 
  Users, 
  CheckCircle2, 
  Clock, 
  AlertCircle 
} from 'lucide-react';
import { 
  BarChart, 
  Bar, 
  XAxis, 
  YAxis, 
  CartesianGrid, 
  Tooltip, 
  ResponsiveContainer, 
  PieChart, 
  Pie, 
  Cell 
} from 'recharts';

interface DashboardProps {
  lots: Lot[];
  projects: Project[];
}

const Dashboard: React.FC<DashboardProps> = ({ lots, projects }) => {
  const stats = [
    { 
      label: 'Lotes Totales', 
      value: lots.length, 
      icon: TrendingUp, 
      color: 'bg-blue-500', 
      text: 'text-blue-600' 
    },
    { 
      label: 'Lotes Disponibles', 
      value: lots.filter(l => l.status === LotStatus.AVAILABLE).length, 
      icon: CheckCircle2, 
      color: 'bg-emerald-500', 
      text: 'text-emerald-600' 
    },
    { 
      label: 'Lotes Reservados', 
      value: lots.filter(l => l.status === LotStatus.RESERVED).length, 
      icon: Clock, 
      color: 'bg-amber-500', 
      text: 'text-amber-600' 
    },
    { 
      label: 'Transferidos', 
      value: lots.filter(l => l.status === LotStatus.TRANSFERRED).length, 
      icon: Users, 
      color: 'bg-slate-500', 
      text: 'text-slate-600' 
    },
  ];

  const chartData = projects.map(p => {
    const projectLots = lots.filter(l => l.projectId === p.id);
    return {
      name: p.name,
      Vendido: projectLots.filter(l => l.status === LotStatus.TRANSFERRED).length,
      Reservado: projectLots.filter(l => l.status === LotStatus.RESERVED).length,
      Libre: projectLots.filter(l => l.status === LotStatus.AVAILABLE).length,
    };
  });

  const pieData = [
    { name: 'Libre', value: lots.filter(l => l.status === LotStatus.AVAILABLE).length },
    { name: 'Reservado', value: lots.filter(l => l.status === LotStatus.RESERVED).length },
    { name: 'Transferido', value: lots.filter(l => l.status === LotStatus.TRANSFERRED).length },
  ];

  const COLORS = ['#10b981', '#f59e0b', '#64748b'];

  return (
    <div className="space-y-8">
      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {stats.map((stat) => (
          <div key={stat.label} className="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
            <div className="flex items-center justify-between mb-4">
              <div className={`${stat.color} p-3 rounded-lg text-white`}>
                <stat.icon className="w-6 h-6" />
              </div>
              <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Hoy</span>
            </div>
            <p className="text-2xl font-bold text-slate-800">{stat.value}</p>
            <p className="text-sm font-medium text-slate-500">{stat.label}</p>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Main Sales Chart */}
        <div className="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-slate-200">
          <h3 className="text-lg font-bold text-slate-800 mb-6">Estado de Lotes por Proyecto</h3>
          <div className="h-[300px]">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={chartData}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                <XAxis dataKey="name" axisLine={false} tickLine={false} />
                <YAxis axisLine={false} tickLine={false} />
                <Tooltip 
                  cursor={{fill: '#f1f5f9'}}
                  contentStyle={{borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)'}}
                />
                <Bar dataKey="Vendido" stackId="a" fill="#64748b" radius={[0, 0, 0, 0]} />
                <Bar dataKey="Reservado" stackId="a" fill="#f59e0b" radius={[0, 0, 0, 0]} />
                <Bar dataKey="Libre" stackId="a" fill="#10b981" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Global Distribution */}
        <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
          <h3 className="text-lg font-bold text-slate-800 mb-6">Distribución Global</h3>
          <div className="h-[250px]">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={pieData}
                  cx="50%"
                  cy="50%"
                  innerRadius={60}
                  outerRadius={80}
                  paddingAngle={5}
                  dataKey="value"
                >
                  {pieData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip />
              </PieChart>
            </ResponsiveContainer>
          </div>
          <div className="mt-4 space-y-2">
            {pieData.map((d, i) => (
              <div key={d.name} className="flex items-center justify-between text-sm">
                <div className="flex items-center gap-2">
                  <div className="w-3 h-3 rounded-full" style={{backgroundColor: COLORS[i]}}></div>
                  <span className="text-slate-600">{d.name}</span>
                </div>
                <span className="font-bold text-slate-800">{d.value}</span>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Recent Activity Mockup */}
      <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
        <h3 className="text-lg font-bold text-slate-800 mb-6">Reservas Recientes</h3>
        <div className="overflow-x-auto">
          <table className="w-full text-left">
            <thead>
              <tr className="text-slate-400 text-xs font-semibold uppercase tracking-wider border-b border-slate-100">
                <th className="pb-3">Lote</th>
                <th className="pb-3">Proyecto</th>
                <th className="pb-3">Cliente</th>
                <th className="pb-3">Monto</th>
                <th className="pb-3 text-right">Estado</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-50">
              {lots.filter(l => l.status === LotStatus.RESERVED).slice(0, 5).map((lot) => (
                <tr key={lot.id} className="text-sm">
                  <td className="py-4 font-medium text-slate-800">{lot.block}-{lot.number}</td>
                  <td className="py-4 text-slate-600">Villa Norte</td>
                  <td className="py-4 text-slate-600">Cliente Muestra</td>
                  <td className="py-4 text-slate-600 font-medium">S/ {lot.price.toLocaleString()}</td>
                  <td className="py-4 text-right">
                    <span className="px-2 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-bold">RESERVADO</span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
