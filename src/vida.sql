--
-- PostgreSQL database dump
--

\restrict CZuSIYDfgGEkFPuupMqS0FB4deckbHTssXaLfjZf9R3qzzkKlZdvfPb0dqueMoG

-- Dumped from database version 15.18 (Debian 15.18-0+deb12u1)
-- Dumped by pg_dump version 15.18 (Debian 15.18-0+deb12u1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: trg_updated_em(); Type: FUNCTION; Schema: public; Owner: deby
--

CREATE FUNCTION public.trg_updated_em() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.updated_em = NOW();
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.trg_updated_em() OWNER TO deby;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: marcacoes; Type: TABLE; Schema: public; Owner: deby
--

CREATE TABLE public.marcacoes (
    id integer NOT NULL,
    ticket character varying(30) NOT NULL,
    data date NOT NULL,
    hora time without time zone,
    cliente character varying(10) DEFAULT 'antigo'::character varying NOT NULL,
    urgencia character varying(10) DEFAULT 'normal'::character varying NOT NULL,
    estado character varying(20) DEFAULT 'Pendente'::character varying NOT NULL,
    medico character varying(150) DEFAULT ''::character varying NOT NULL,
    processo character varying(50) DEFAULT ''::character varying NOT NULL,
    criado_em timestamp without time zone DEFAULT now() NOT NULL,
    updated_em timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT marcacoes_cliente_check CHECK (((cliente)::text = ANY (ARRAY[('novo'::character varying)::text, ('antigo'::character varying)::text]))),
    CONSTRAINT marcacoes_estado_check CHECK (((estado)::text = ANY (ARRAY[('Pendente'::character varying)::text, ('Concluido'::character varying)::text, ('Cancelado'::character varying)::text]))),
    CONSTRAINT marcacoes_urgencia_check CHECK (((urgencia)::text = ANY (ARRAY[('normal'::character varying)::text, ('urgente'::character varying)::text])))
);


ALTER TABLE public.marcacoes OWNER TO deby;

--
-- Name: marcacoes_id_seq; Type: SEQUENCE; Schema: public; Owner: deby
--

CREATE SEQUENCE public.marcacoes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.marcacoes_id_seq OWNER TO deby;

--
-- Name: marcacoes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: deby
--

ALTER SEQUENCE public.marcacoes_id_seq OWNED BY public.marcacoes.id;


--
-- Name: notificacoes; Type: TABLE; Schema: public; Owner: deby
--

CREATE TABLE public.notificacoes (
    id integer NOT NULL,
    ativa boolean DEFAULT false NOT NULL,
    mensagem_pt text DEFAULT ''::text NOT NULL,
    mensagem_en text DEFAULT ''::text NOT NULL,
    criado_em timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.notificacoes OWNER TO deby;

--
-- Name: notificacoes_id_seq; Type: SEQUENCE; Schema: public; Owner: deby
--

CREATE SEQUENCE public.notificacoes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.notificacoes_id_seq OWNER TO deby;

--
-- Name: notificacoes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: deby
--

ALTER SEQUENCE public.notificacoes_id_seq OWNED BY public.notificacoes.id;


--
-- Name: staff; Type: TABLE; Schema: public; Owner: deby
--

CREATE TABLE public.staff (
    id integer NOT NULL,
    username character varying(50) NOT NULL,
    chave character varying(100) NOT NULL,
    nome character varying(150) NOT NULL,
    tipo character varying(20) NOT NULL,
    clinica boolean DEFAULT false NOT NULL,
    criado_em timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT staff_tipo_check CHECK (((tipo)::text = ANY (ARRAY[('medico'::character varying)::text, ('recepcionista'::character varying)::text])))
);


ALTER TABLE public.staff OWNER TO deby;

--
-- Name: staff_id_seq; Type: SEQUENCE; Schema: public; Owner: deby
--

CREATE SEQUENCE public.staff_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.staff_id_seq OWNER TO deby;

--
-- Name: staff_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: deby
--

ALTER SEQUENCE public.staff_id_seq OWNED BY public.staff.id;


--
-- Name: marcacoes id; Type: DEFAULT; Schema: public; Owner: deby
--

ALTER TABLE ONLY public.marcacoes ALTER COLUMN id SET DEFAULT nextval('public.marcacoes_id_seq'::regclass);


--
-- Name: notificacoes id; Type: DEFAULT; Schema: public; Owner: deby
--

ALTER TABLE ONLY public.notificacoes ALTER COLUMN id SET DEFAULT nextval('public.notificacoes_id_seq'::regclass);


--
-- Name: staff id; Type: DEFAULT; Schema: public; Owner: deby
--

ALTER TABLE ONLY public.staff ALTER COLUMN id SET DEFAULT nextval('public.staff_id_seq'::regclass);


--
-- Data for Name: marcacoes; Type: TABLE DATA; Schema: public; Owner: deby
--

COPY public.marcacoes (id, ticket, data, hora, cliente, urgencia, estado, medico, processo, criado_em, updated_em) FROM stdin;
1	#V-0001	2026-01-05	\N	antigo	normal	Concluido	Dr. Armando Silva	P-100	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
2	#V-0002	2026-01-12	\N	novo	urgente	Concluido	Dr.ª Luísa Mário	P-101	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
3	#V-0003	2026-05-06	\N	antigo	normal	Concluido	Dr. Carlos Nhaca	1016	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
4	#V-0004	2026-01-20	\N	novo	normal	Concluido	Dr. Armando Silva	P-102	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
5	#V-0005	2026-01-25	\N	antigo	urgente	Concluido	Dr.ª Luísa Mário	P-103	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
6	#V-0006	2026-01-28	\N	novo	normal	Concluido	Dr. Armando Silva	P-104	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
7	#V-0101	2026-02-02	\N	antigo	normal	Concluido	Dr.ª Luísa Mário	P-105	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
8	#V-0102	2026-02-03	\N	novo	urgente	Concluido	Dr. Armando Silva	P-106	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
9	#V-0103	2026-05-06	\N	antigo	normal	Concluido	Dr. Armando Silva	1018	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
10	#V-0104	2026-02-07	\N	novo	normal	Concluido	Dr.ª Luísa Mário	P-107	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
11	#V-0105	2026-02-10	\N	antigo	normal	Concluido	Dr. Armando Silva	P-108	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
12	#V-0106	2026-02-12	\N	novo	urgente	Concluido	Dr.ª Luísa Mário	P-109	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
13	#V-0107	2026-02-14	\N	antigo	normal	Concluido	Dr. Armando Silva	P-110	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
14	#V-0108	2026-05-04	\N	novo	normal	Concluido	Dr.ª Luísa Mário	1013	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
15	#V-0109	2026-02-18	\N	antigo	urgente	Concluido	Dr.ª Luísa Mário	P-111	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
16	#V-0110	2026-02-20	\N	novo	normal	Concluido	Dr. Armando Silva	P-112	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
17	#V-0111	2026-02-22	\N	antigo	normal	Concluido	Dr.ª Luísa Mário	P-113	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
18	#V-0112	2026-02-24	\N	novo	urgente	Concluido	Dr. Armando Silva	P-114	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
19	#V-0113	2026-02-26	\N	antigo	normal	Concluido	Dr.ª Luísa Mário	P-115	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
20	#V-0114	2026-02-28	\N	novo	normal	Concluido	Dr. Armando Silva	P-116	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
21	#V-1001	2026-03-05	\N	antigo	normal	Concluido	Dr. Armando Silva	P-500	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
22	#V-1002	2026-03-05	\N	novo	normal	Concluido	Dr. Armando Silva	P-501	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
23	#V-1003	2026-03-07	\N	antigo	urgente	Concluido	Dr.ª Luísa Mário	P-220	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
24	#V-1004	2026-03-10	\N	novo	normal	Concluido	Dr. Armando Silva	P-502	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
25	#V-1005	2026-03-12	\N	antigo	normal	Concluido	Dr.ª Luísa Mário	P-115	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
26	#V-1006	2026-03-15	\N	novo	urgente	Concluido	Dr. Armando Silva	P-503	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
27	#V-1007	2026-03-18	\N	antigo	normal	Concluido	Dr.ª Luísa Mário	P-090	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
28	#V-1008	2026-03-20	\N	novo	normal	Concluido	Dr. Armando Silva	P-504	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
29	#V-1009	2026-03-22	\N	antigo	urgente	Concluido	Dr.ª Luísa Mário	P-310	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
30	#V-1010	2026-03-25	\N	novo	normal	Concluido	Dr. Armando Silva	P-505	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
31	#V-2001	2026-04-01	\N	antigo	normal	Concluido	Dr. Armando Silva	P-400	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
32	#V-2002	2026-04-02	\N	novo	urgente	Concluido	Dr.ª Luísa Mário	P-506	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
33	#V-2003	2026-04-03	\N	antigo	normal	Concluido	Dr. Armando Silva	P-401	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
34	#V-2004	2026-04-05	\N	novo	normal	Concluido	Dr.ª Luísa Mário	P-507	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
35	#V-2005	2026-04-06	\N	antigo	urgente	Concluido	Dr. Armando Silva	P-402	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
36	#V-2006	2026-04-07	\N	novo	normal	Concluido	Dr.ª Luísa Mário	P-508	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
37	#V-2007	2026-04-08	\N	antigo	normal	Concluido	Dr. Armando Silva	P-403	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
38	#V-2008	2026-04-09	\N	novo	urgente	Concluido	Dr.ª Luísa Mário	P-509	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
39	#V-2009	2026-04-10	\N	antigo	normal	Concluido	Dr. Armando Silva	P-404	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
40	#V-2010	2026-04-11	\N	novo	normal	Concluido	Dr.ª Luísa Mário	P-510	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
41	#V-6079	2026-05-06	\N	novo	urgente	Pendente	Dr. Armando Silva	1017	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
42	#V-7240	2026-05-04	\N	antigo	normal	Concluido	Dr. Armando Silva	1012	2026-05-17 18:24:04.527839	2026-05-17 18:24:04.527839
43	V-81ECE5	2026-05-04	\N	novo	normal	Concluido	Dr. Armando Silva	1014	2026-04-15 06:49:10	2026-05-17 18:24:04.527839
44	V-5D9973	2026-05-04	\N	antigo	normal	Concluido	Dr.ª Luísa Mário	1015	2026-04-15 06:57:50	2026-05-17 18:24:04.527839
45	V-9A07DB	2026-04-27	\N	novo	urgente	Concluido	Dr. Armando Silva	0002	2026-04-25 17:50:54	2026-05-17 18:24:04.527839
47	V-9DCA4B	2026-05-18	\N	novo	urgente	Concluido	Dr.ª Luísa Mário	P-1192	2026-04-25 17:52:58	2026-05-17 22:32:59.114647
46	V-11BC82	2026-05-18	\N	novo	urgente	Concluido	Dr. Armando Silva	1019	2026-04-25 17:52:55	2026-05-17 22:33:29.675852
48	V-F627E7	2026-05-18	\N	novo	normal	Concluido	Dr. Carlos Nhaca	P-1318	2026-04-28 15:24:59	2026-05-18 10:02:01.837499
53	V-AB186D	2026-05-18	\N	novo	urgente	Pendente			2026-05-18 10:03:10.503951	2026-05-18 10:03:10.503951
54	V-0AA7D4	2026-05-18	\N	antigo	normal	Pendente			2026-05-18 10:03:17.22253	2026-05-18 10:03:17.22253
55	V-D48C57	2026-05-18	\N	novo	urgente	Pendente			2026-05-18 10:03:24.09501	2026-05-18 10:03:24.09501
56	V-E59983	2026-05-18	\N	novo	urgente	Pendente			2026-05-18 10:07:21.515487	2026-05-18 10:07:21.515487
57	V-56EC21	2026-05-18	\N	antigo	normal	Pendente			2026-05-18 10:07:27.874098	2026-05-18 10:07:27.874098
52	V-B22E82	2026-05-18	\N	antigo	urgente	Concluido	Dr.ª Luísa Mário	P-2510	2026-05-18 10:00:59.000375	2026-05-18 11:02:06.142592
49	V-3FDFC9	2026-05-18	\N	novo	urgente	Pendente	Dr. Armando Silva	P-1006	2026-04-28 15:25:54	2026-05-18 11:02:41.087032
58	V-D8ACC4	2026-05-18	\N	antigo	urgente	Concluido	Dr.ª Luísa Mário	P-5256	2026-05-18 10:07:36.570024	2026-05-18 11:04:44.033531
89	V-8DD304	2026-05-19	\N	novo	normal	Concluido	Dr.ª Luísa Mário	P-5532	2026-05-19 10:04:20.050939	2026-05-19 10:11:52.177301
91	V-A3E4D7	2026-05-19	\N	novo	urgente	Pendente	Dr. Armando Silva	P-4352	2026-05-19 10:08:35.970362	2026-05-19 10:12:03.170436
90	V-7DD3D4	2026-05-19	\N	antigo	urgente	Concluido	Dr. Armando Silva	P-2442	2026-05-19 10:08:29.267053	2026-05-19 10:12:27.928019
92	V-EE0E7F	2026-05-19	\N	novo	urgente	Concluido	Dr. Carlos Nhaca	P-2004	2026-05-19 10:21:31.993554	2026-05-19 10:23:20.614365
93	V-377361	2026-05-19	\N	antigo	normal	Concluido	Dr. Armando Silva	P-5006	2026-05-19 10:23:45.898324	2026-05-19 10:26:21.270899
94	V-7D9996	2026-05-26	\N	antigo	normal	Pendente			2026-05-19 11:01:59.902385	2026-05-19 11:01:59.902385
51	V-28C0FC	2026-05-20	\N	antigo	urgente	Concluido	Dr. Carlos Nhaca	P-6565	2026-05-17 22:31:09.230125	2026-05-19 21:14:34.889208
96	V-5E6982	2026-06-01	\N	novo	urgente	Concluido	Dr.ª Luísa Mário	P-5587	2026-05-29 19:39:20.636556	2026-06-01 09:49:13.169485
50	V-ACEF31	2026-06-01	\N	novo	urgente	Concluido	Dr. Armando Silva	P-1951	2026-05-17 22:28:36.275466	2026-06-01 09:54:58.533718
99	V-11EEC9	2026-06-01	\N	novo	urgente	Concluido	Dr. Carlos Nhaca	P-7383	2026-06-01 11:00:31.125625	2026-06-01 11:01:16.198731
100	V-2D7174	2026-06-01	\N	novo	normal	Pendente			2026-06-01 11:10:22.771131	2026-06-01 11:10:22.771131
101	V-A0B2DB	2026-06-01	\N	antigo	normal	Pendente			2026-06-01 11:40:25.735313	2026-06-01 11:40:25.735313
98	V-7C36BB	2026-06-01	\N	antigo	urgente	Concluido	Dr.ª Luísa Mário	P-1367	2026-06-01 09:55:53.212886	2026-06-01 11:52:05.108509
97	V-931E04	2026-06-01	\N	antigo	urgente	Concluido	Dr. Armando Silva	P-6777	2026-06-01 09:37:39.047387	2026-06-01 12:08:59.568266
95	V-035187	2026-06-01	\N	antigo	normal	Concluido	Dr. Armando Silva	P-1537	2026-05-29 19:39:12.182116	2026-06-01 12:07:21.460857
\.


--
-- Data for Name: notificacoes; Type: TABLE DATA; Schema: public; Owner: deby
--

COPY public.notificacoes (id, ativa, mensagem_pt, mensagem_en, criado_em) FROM stdin;
1	f	As consultas para dia 2 vao estar adiadas		2026-06-01 11:55:23.236568
\.


--
-- Data for Name: staff; Type: TABLE DATA; Schema: public; Owner: deby
--

COPY public.staff (id, username, chave, nome, tipo, clinica, criado_em) FROM stdin;
1	armando	$2y$10$NkAP6n2VqvEQ1alnQXOBxee8i8raWbY4cplxZtaYDP2AnKOfu4KBS	Dr. Armando Silva	medico	t	2026-05-17 18:20:33.934376
2	maria	$2y$10$8tu/PyaywE1FqnOpavKIy.qYrXr8.Oooq06UUt7N02DRYgJaY1MO6	Maria Santos	recepcionista	f	2026-05-17 18:20:33.934376
3	luisa	$2y$10$eWu7d1MPOKenw44x0qY2fezZlVYB0G6t2KuoZB8ln.2/f.6xbDYQe	Luísa Mário	recepcionista	f	2026-05-17 18:20:33.934376
\.


--
-- Name: marcacoes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: deby
--

SELECT pg_catalog.setval('public.marcacoes_id_seq', 101, true);


--
-- Name: notificacoes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: deby
--

SELECT pg_catalog.setval('public.notificacoes_id_seq', 1, true);


--
-- Name: staff_id_seq; Type: SEQUENCE SET; Schema: public; Owner: deby
--

SELECT pg_catalog.setval('public.staff_id_seq', 3, true);


--
-- Name: marcacoes marcacoes_pkey; Type: CONSTRAINT; Schema: public; Owner: deby
--

ALTER TABLE ONLY public.marcacoes
    ADD CONSTRAINT marcacoes_pkey PRIMARY KEY (id);


--
-- Name: marcacoes marcacoes_ticket_key; Type: CONSTRAINT; Schema: public; Owner: deby
--

ALTER TABLE ONLY public.marcacoes
    ADD CONSTRAINT marcacoes_ticket_key UNIQUE (ticket);


--
-- Name: notificacoes notificacoes_pkey; Type: CONSTRAINT; Schema: public; Owner: deby
--

ALTER TABLE ONLY public.notificacoes
    ADD CONSTRAINT notificacoes_pkey PRIMARY KEY (id);


--
-- Name: staff staff_pkey; Type: CONSTRAINT; Schema: public; Owner: deby
--

ALTER TABLE ONLY public.staff
    ADD CONSTRAINT staff_pkey PRIMARY KEY (id);


--
-- Name: staff staff_username_key; Type: CONSTRAINT; Schema: public; Owner: deby
--

ALTER TABLE ONLY public.staff
    ADD CONSTRAINT staff_username_key UNIQUE (username);


--
-- Name: idx_marcacoes_data; Type: INDEX; Schema: public; Owner: deby
--

CREATE INDEX idx_marcacoes_data ON public.marcacoes USING btree (data);


--
-- Name: idx_marcacoes_estado; Type: INDEX; Schema: public; Owner: deby
--

CREATE INDEX idx_marcacoes_estado ON public.marcacoes USING btree (estado);


--
-- Name: idx_marcacoes_medico; Type: INDEX; Schema: public; Owner: deby
--

CREATE INDEX idx_marcacoes_medico ON public.marcacoes USING btree (medico);


--
-- Name: idx_marcacoes_ticket; Type: INDEX; Schema: public; Owner: deby
--

CREATE INDEX idx_marcacoes_ticket ON public.marcacoes USING btree (ticket);


--
-- Name: marcacoes set_updated_em; Type: TRIGGER; Schema: public; Owner: deby
--

CREATE TRIGGER set_updated_em BEFORE UPDATE ON public.marcacoes FOR EACH ROW EXECUTE FUNCTION public.trg_updated_em();


--
-- PostgreSQL database dump complete
--

\unrestrict CZuSIYDfgGEkFPuupMqS0FB4deckbHTssXaLfjZf9R3qzzkKlZdvfPb0dqueMoG

