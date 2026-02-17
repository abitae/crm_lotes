
import React, { useState, useMemo } from 'react';
import { 
  LayoutDashboard, 
  Map as MapIcon, 
  DollarSign, 
  Users, 
  BarChart3, 
  Building2,
  ChevronRight,
  Menu,
  X,
  Plus,
  Search,
  LogOut,
  UserCheck,
  Percent
} from 'lucide-react';
import { ViewType, Lot, Project, Client, Advisor } from './types';
import { PROJECTS, INITIAL_LOTS, MOCK_CLIENTS, ADVISORS } from './constants';
import Dashboard from './components/Dashboard';
import Inventory from './components/Inventory';
import FinancialControl from './components/FinancialControl';
import ClientsView from './components/ClientsView';
import Reports from './components/Reports';
import SellersView from './components/SellersView';
import CommissionsView from './components/CommissionsView';

const App: React.FC = () => {
  const [activeView, setActiveView] = useState<ViewType>('dashboard');
  const [isSidebarOpen, setIsSidebarOpen] = useState(true);
  const [selectedProject, setSelectedProject] = useState<Project>(PROJECTS[0]);
  const [lots, setLots] = useState<Lot[]>(INITIAL_LOTS);
  const [clients, setClients] = useState<Client[]>(MOCK_CLIENTS);
  const [advisors, setAdvisors] = useState<Advisor[]>(ADVISORS);

  const navigation = [
    { name: 'Dashboard', icon: LayoutDashboard, view: 'dashboard' as ViewType },
    { name: 'Inventario de Lotes', icon: MapIcon, view: 'inventory' as ViewType },
    { name: 'Control Financiero', icon: DollarSign, view: 'finance' as ViewType },
    { name: 'Comisiones', icon: Percent, view: 'commissions' as ViewType },
    { name: 'Clientes', icon: Users, view: 'clients' as ViewType },
    { name: 'Vendedores', icon: UserCheck, view: 'sellers' as ViewType },
    { name: 'Reportes', icon: BarChart3, view: 'reports' as ViewType },
  ];

  const updateLot = (updatedLot: Lot) => {
    setLots(prev => prev.map(l => l.id === updatedLot.id ? updatedLot : l));
  };

  const renderView = () => {
    switch (activeView) {
      case 'dashboard':
        return <Dashboard lots={lots} projects={PROJECTS} />;
      case 'inventory':
        return (
          <Inventory 
            selectedProject={selectedProject} 
            lots={lots.filter(l => l.projectId === selectedProject.id)}
            onUpdateLot={updateLot}
            clients={clients}
            advisors={advisors}
          />
        );
      case 'finance':
        return <FinancialControl lots={lots} projects={PROJECTS} clients={clients} />;
      case 'commissions':
        return <CommissionsView lots={lots} advisors={advisors} projects={PROJECTS} />;
      case 'clients':
        return <ClientsView clients={clients} lots={lots} projects={PROJECTS} />;
      case 'sellers':
        return <SellersView advisors={advisors} lots={lots} />;
      case 'reports':
        return <Reports lots={lots} advisors={advisors} />;
      default:
        return <Dashboard lots={lots} projects={PROJECTS} />;
    }
  };

  return (
    <div className="flex h-screen bg-slate-100 overflow-hidden font-sans">
      {/* Sidebar */}
      <aside 
        className={`${
          isSidebarOpen ? 'w-64' : 'w-20'
        } bg-slate-900 text-white transition-all duration-300 flex flex-col z-50`}
      >
        <div className="p-6 flex items-center justify-between">
          {isSidebarOpen ? (
            <div className="flex items-center gap-2">
              <Building2 className="w-8 h-8 text-emerald-400" />
              <span className="font-bold text-xl tracking-tight">InmoPro</span>
            </div>
          ) : (
            <Building2 className="w-8 h-8 text-emerald-400 mx-auto" />
          )}
          <button 
            onClick={() => setIsSidebarOpen(!isSidebarOpen)}
            className="md:hidden"
          >
            <X className="w-6 h-6" />
          </button>
        </div>

        <nav className="flex-1 mt-6 px-4 space-y-2 overflow-y-auto custom-scrollbar">
          {navigation.map((item) => (
            <button
              key={item.name}
              onClick={() => setActiveView(item.view)}
              className={`w-full flex items-center gap-4 p-3 rounded-lg transition-colors ${
                activeView === item.view 
                ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/50' 
                : 'text-slate-400 hover:bg-slate-800 hover:text-white'
              }`}
            >
              <item.icon className="w-6 h-6 shrink-0" />
              {isSidebarOpen && <span className="font-medium whitespace-nowrap">{item.name}</span>}
            </button>
          ))}
        </nav>

        <div className="p-4 border-t border-slate-800">
          <button className="w-full flex items-center gap-4 p-3 text-slate-400 hover:text-red-400 transition-colors">
            <LogOut className="w-6 h-6 shrink-0" />
            {isSidebarOpen && <span className="font-medium">Cerrar Sesión</span>}
          </button>
        </div>
      </aside>

      {/* Main Content */}
      <main className="flex-1 flex flex-col min-w-0 overflow-hidden">
        {/* Header */}
        <header className="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-8 shrink-0">
          <div className="flex items-center gap-4">
            <button 
              onClick={() => setIsSidebarOpen(!isSidebarOpen)}
              className="p-2 text-slate-600 hover:bg-slate-100 rounded-md hidden md:block transition-colors"
            >
              <Menu className="w-6 h-6" />
            </button>
            <h2 className="text-xl font-bold text-slate-800 capitalize">
              {navigation.find(n => n.view === activeView)?.name}
            </h2>
          </div>

          <div className="flex items-center gap-6">
            {activeView === 'inventory' && (
              <div className="relative group">
                <Building2 className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-emerald-500 transition-colors" />
                <select 
                  value={selectedProject.id}
                  onChange={(e) => setSelectedProject(PROJECTS.find(p => p.id === e.target.value)!)}
                  className="bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 block w-64 p-2.5 pl-10 outline-none transition-all cursor-pointer font-bold appearance-none"
                >
                  {PROJECTS.map(p => (
                    <option key={p.id} value={p.id}>{p.name.toUpperCase()}</option>
                  ))}
                </select>
              </div>
            )}
            
            <div className="flex items-center gap-3 pl-4 border-l border-slate-200">
              <div className="text-right hidden sm:block">
                <p className="text-sm font-bold text-slate-900 leading-none">Admin Usuario</p>
                <p className="text-[10px] font-black text-emerald-600 uppercase mt-1 tracking-widest">Master Admin</p>
              </div>
              <div className="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-white font-black text-sm border-2 border-slate-800 shadow-md">
                AD
              </div>
            </div>
          </div>
        </header>

        {/* Content Area */}
        <div className="flex-1 overflow-y-auto p-8 bg-slate-50/50">
          {renderView()}
        </div>
      </main>
    </div>
  );
};

export default App;
