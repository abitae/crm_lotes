import { Link } from '@inertiajs/react';
import LegalDocumentLayout from '@/layouts/legal-document-layout';
import { publisherContact } from '@/legal/publisher-contact';
import legal from '@/routes/legal';

const sectionTitle = 'mt-10 text-lg font-semibold text-slate-900 first:mt-0 dark:text-white';
const paragraph = 'mt-3 text-sm leading-relaxed text-slate-700 dark:text-slate-300';
const list = 'mt-3 list-disc space-y-2 pl-5 text-sm leading-relaxed text-slate-700 dark:text-slate-300';

export default function LegalPrivacy() {
    return (
        <LegalDocumentLayout title="Política de privacidad" headTitle="Política de privacidad">
            <div className="space-y-2">
                <p className={paragraph}>
                    Esta política describe cómo se trata la información personal y operativa cuando utiliza la aplicación
                    gestionada por su organización.
                </p>
                <p className={paragraph}>
                    <span className="font-medium text-slate-800 dark:text-slate-200">Editor del servicio y contacto:</span>{' '}
                    {publisherContact.fullName}. Para consultas sobre esta política, privacidad o datos personales
                    relacionados con la aplicación, puede escribir a{' '}
                    <a
                        href={`mailto:${publisherContact.email}`}
                        className="font-medium text-emerald-600 underline-offset-2 hover:underline dark:text-emerald-400"
                    >
                        {publisherContact.email}
                    </a>
                    .
                </p>
                <p className={paragraph}>
                    En entornos corporativos, el responsable del tratamiento de los datos introducidos en el sistema puede
                    ser además la entidad que administra el despliegue (empleador, sociedad matriz o cliente corporativo
                    según corresponda).
                </p>

                <h2 className={sectionTitle}>1. Datos que podemos tratar</h2>
                <p className={paragraph}>Según los módulos activos, esto puede incluir:</p>
                <ul className={list}>
                    <li>Datos de identificación y contacto de usuarios corporativos (nombre, correo, rol).</li>
                    <li>Datos de clientes, prospectos o terceros introducidos en el curso normal del negocio inmobiliario.</li>
                    <li>Datos de actividad técnica necesarios para seguridad y auditoría (inicios de sesión, dirección IP, registros de uso).</li>
                    <li>Contenidos cargados voluntariamente (documentos, notas, archivos adjuntos cuando el sistema lo permita).</li>
                </ul>

                <h2 className={sectionTitle}>2. Finalidades</h2>
                <ul className={list}>
                    <li>Prestar y mantener las funcionalidades contratadas (gestión de proyectos, lotes, cobros, reportes, etc.).</li>
                    <li>Autenticar usuarios, aplicar permisos y proteger la integridad de la información.</li>
                    <li>Cumplir obligaciones legales aplicables y requerimientos de la organización.</li>
                    <li>Mejorar la estabilidad y seguridad del servicio (análisis agregados o técnicos, sin perjuicio de acuerdos específicos).</li>
                </ul>

                <h2 className={sectionTitle}>3. Base legal</h2>
                <p className={paragraph}>
                    El tratamiento se fundamenta en la ejecución de medidas precontractuales o contractuales, el interés
                    legítimo de la organización para operar de forma segura, el cumplimiento de obligaciones legales y,
                    cuando proceda, el consentimiento del interesado.
                </p>

                <h2 className={sectionTitle}>4. Conservación</h2>
                <p className={paragraph}>
                    Los datos se conservan el tiempo necesario para las finalidades indicadas y según los plazos legales o
                    políticas de archivo de la organización. Pasado ese plazo, se suprimen o anonimizan cuando sea posible.
                </p>

                <h2 className={sectionTitle}>5. Cesiones y encargados</h2>
                <p className={paragraph}>
                    No se venden datos personales. Pueden existir encargados del tratamiento (por ejemplo, proveedores de
                    infraestructura o alojamiento) con contratos o cláusulas que exigen confidencialidad y medidas de
                    seguridad adecuadas. Toda cesión adicional se realizará conforme a la ley.
                </p>

                <h2 className={sectionTitle}>6. Derechos de las personas interesadas</h2>
                <p className={paragraph}>
                    Las personas cuyos datos personales figuren en el sistema pueden ejercer los derechos reconocidos por
                    la normativa aplicable (acceso, rectificación, supresión, oposición, limitación, portabilidad u
                    otros). Puede dirigir su solicitud al correo{' '}
                    <a
                        href={`mailto:${publisherContact.email}`}
                        className="font-medium text-emerald-600 underline-offset-2 hover:underline dark:text-emerald-400"
                    >
                        {publisherContact.email}
                    </a>{' '}
                    ({publisherContact.fullName}). En entornos corporativos, las solicitudes pueden canalizarse también a
                    través del responsable del tratamiento en su organización o del delegado de protección de datos, si
                    existiera.
                </p>

                <h2 className={sectionTitle}>7. Seguridad</h2>
                <p className={paragraph}>
                    Se aplican medidas técnicas y organizativas razonables para proteger la información frente a accesos
                    no autorizados, pérdida o alteración. Ningún sistema es invulnerable; se recomienda el uso de
                    contraseñas robustas y la protección de los dispositivos desde los que se accede.
                </p>

                <h2 className={sectionTitle}>8. Cambios</h2>
                <p className={paragraph}>
                    Esta política puede actualizarse para reflejar cambios legales o en el servicio. La fecha de «última
                    actualización» en la parte superior de la página indicará la revisión vigente.
                </p>

                <p className={`${paragraph} mt-8 border-t border-slate-200 pt-8 dark:border-slate-800`}>
                    Consulte también los{' '}
                    <Link
                        href={legal.terms()}
                        className="font-medium text-emerald-600 underline-offset-2 hover:underline dark:text-emerald-400"
                    >
                        términos y condiciones de uso
                    </Link>
                    .
                </p>
            </div>
        </LegalDocumentLayout>
    );
}
